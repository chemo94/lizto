<?php

namespace App\Services;

use App\Models\{SunatInvoice, SellerCompany};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoidedCancellation
{
    public function eligibilityError(SunatInvoice $source): ?string
    {
        if ($source->tipo_doc !== '01' || $source->cdr_status !== 'accepted') {
            return 'La baja RA requiere una factura aceptada.';
        }
        if (!$source->fecha_emision) return 'Falta la fecha de generación de la factura.';
        $date = Carbon::parse($source->fecha_emision->format('Y-m-d'), 'America/Lima')->startOfDay();
        $today = now('America/Lima')->startOfDay();
        // RS 000048-2026/SUNAT, art. 14.1.b, vigente desde 01/08/2026.
        if ($date->gt($today) || $today->gt($date->copy()->addDays(7))) {
            return 'Fuera del plazo de siete días calendario desde el día siguiente de la generación. Evalúa una nota de crédito FC.';
        }
        return null;
    }

    public function reserve(SunatInvoice $source, string $reason, bool $notGranted): SunatInvoice
    {
        if (!$notGranted) throw new RuntimeException('Debes declarar que la factura no fue entregada ni puesta a disposición del cliente.');
        if (trim($reason) === '' || mb_strlen($reason) > 100) throw new RuntimeException('Indica un motivo de baja de hasta 100 caracteres.');
        return DB::transaction(function () use ($source, $reason) {
            $original = SunatInvoice::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $previous = SunatInvoice::where('original_invoice_id', $original->id)->where('cdr_status', '!=', 'rejected')->latest('id')->first();
            if ($previous) {
                if ($previous->tipo_doc !== 'RA') throw new RuntimeException('Existe una nota de crédito en curso o aceptada. Revisa ese documento.');
                return $previous;
            }
            if ($error = $this->eligibilityError($original)) throw new RuntimeException($error);
            $company = SellerCompany::whereKey($original->seller_company_id)->where('seller_id', $original->seller_id)->lockForUpdate()->firstOrFail();
            if ($original->pos_order_id && SunatInvoice::where('seller_company_id', $company->id)->where('pos_order_id', $original->pos_order_id)
                ->whereIn('tipo_doc', ['07', 'RA'])->whereNull('original_invoice_id')->where('cdr_status', '!=', 'rejected')->exists()) {
                throw new RuntimeException('Existen notas o bajas anteriores de esta venta. Revisa su estado antes de solicitar otra baja.');
            }
            $date = now('America/Lima');
            $series = 'RA-'.$date->format('Ymd');
            $number = 1 + (int) SunatInvoice::where('seller_company_id', $company->id)->where('tipo_doc', 'RA')->where('serie', $series)->max('correlativo');
            if ($number > 99999) throw new RuntimeException('Se agotó la numeración diaria de comunicaciones de baja.');
            $xml = app()->make(SunatService::class, ['entity' => $company])->buildVoidedXml($original, $number, $reason, $date);
            return SunatInvoice::create([
                'seller_id'=>$original->seller_id, 'seller_company_id'=>$company->id, 'pos_order_id'=>$original->pos_order_id,
                'original_invoice_id'=>$original->id, 'tipo_doc'=>'RA', 'serie'=>$series, 'correlativo'=>$number,
                'fecha_emision'=>$date, 'note_description'=>trim($reason), 'note_motivo'=>'not_granted', 'note_affected_type'=>'01',
                'cliente_tipo_doc'=>$original->cliente_tipo_doc, 'cliente_num_doc'=>$original->cliente_num_doc,
                'cliente_nombre'=>$original->cliente_nombre, 'cliente_direccion'=>$original->cliente_direccion,
                'total'=>0, 'moneda'=>$original->moneda, 'xml_content'=>$xml, 'cdr_status'=>'generated',
            ]);
        });
    }

    public function submit(SunatInvoice $ra): SunatInvoice
    {
        $ra->refresh();
        if ($ra->tipo_doc !== 'RA' || !$ra->original_invoice_id) throw new RuntimeException('Comunicación de baja no vinculada.');
        if ($ra->cdr_status === 'accepted') {
            app(CreditNoteCancellation::class)->applyCancellation($ra);
            return $ra->refresh();
        }
        if ($ra->cdr_status === 'rejected') throw new RuntimeException('La baja fue rechazada. Revisa el CDR antes de solicitar otra.');
        $claimed = SunatInvoice::whereKey($ra->id)->whereNotIn('cdr_status', ['accepted','rejected'])
            ->where(fn($q) => $q->whereNull('note_processing_at')->orWhere('note_processing_at', '<', now()->subMinutes(5)))
            ->update(['note_processing_at'=>now()]);
        if (!$claimed) throw new RuntimeException('La comunicación ya está en proceso.');
        try {
            $ra->refresh();
            $service = app()->make(SunatService::class, ['entity'=>$ra->company]);
            if (!$ra->ticket) {
                $original = SunatInvoice::findOrFail($ra->original_invoice_id);
                if ($error = $this->eligibilityError($original)) throw new RuntimeException($error);
                // Persist the ticket before consulting; never send a second RA once a ticket exists.
                $ra->update($service->transmitVoidedXml($ra));
            }
            if ($ra->ticket) $ra->update($service->consultVoidedTicket($ra->ticket));
        } catch (\Throwable $e) {
            $ra->update(['cdr_status'=>$ra->ticket ? 'pending' : 'error', 'errors'=>json_encode([['message'=>$e->getMessage()]], JSON_UNESCAPED_UNICODE)]);
            throw $e;
        } finally {
            $ra->update(['note_processing_at'=>null]);
        }
        if ($ra->cdr_status === 'accepted') app(CreditNoteCancellation::class)->applyCancellation($ra);
        return $ra->refresh();
    }
}
