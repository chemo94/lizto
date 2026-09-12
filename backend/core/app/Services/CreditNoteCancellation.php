<?php



namespace App\Services;



use App\Models\{SunatInvoice, PosInvoiceSeries, PosOrder, PosCashSession, PosTransaction};

use Illuminate\Support\Facades\DB;

use RuntimeException;



class CreditNoteCancellation

{

    public function reserve(SunatInvoice $source, int $seriesId, string $reason, string $description, array $input = []): SunatInvoice

    {

        return DB::transaction(function () use ($source,$seriesId,$reason,$description,$input) {

            $original=SunatInvoice::whereKey($source->id)->lockForUpdate()->firstOrFail();

            $previous=SunatInvoice::where('original_invoice_id',$original->id)->whereNotIn('cdr_status',[SunatInvoice::STATUS_REJECTED,SunatInvoice::STATUS_ACCEPTED])->latest('id')->first();

            if($previous) {
                if ($previous->tipo_doc !== '07') throw new RuntimeException('Existe una comunicación de baja en curso o aceptada. Revisa su estado antes de emitir una nota de crédito.');
                return $previous;
            }

            if(!in_array($original->tipo_doc,['01','03']) || $original->cdr_status !== SunatInvoice::STATUS_ACCEPTED) throw new RuntimeException('El comprobante original debe estar aceptado antes de emitir su nota de crédito.');

            // Older notes lack the explicit link: inspect their UBL reference before reserving another number.

            $legacy=SunatInvoice::where('seller_company_id',$original->seller_company_id)->where('tipo_doc','07')->whereNull('original_invoice_id')->where('cdr_status','!=','rejected')->where('pos_order_id',$original->pos_order_id)->get();

            foreach($legacy as $existing) {

                if(preg_match('/<[^>]*ReferenceID[^>]*>\s*'.preg_quote($original->serie,'/').'-0*'.(int)$original->correlativo.'\s*<\//', (string)$existing->xml_content)) throw new RuntimeException('Ya existe una nota de crédito anterior vinculada a este comprobante. Revisa su estado antes de emitir otra.');

            }

            $store=\App\Models\Store::where('seller_id',$original->seller_id)->first();

            if($store && $store->hasReachedInvoiceLimit()) throw new RuntimeException('Se alcanzó el límite de comprobantes electrónicos del plan.');

            $company=$original->company;

            if(!$company || (int)$company->seller_id !== (int)$original->seller_id) throw new RuntimeException('No se pudo identificar la empresa emisora original.');

            $series=PosInvoiceSeries::whereKey($seriesId)->where('seller_company_id',$company->id)->where('active',true)

                ->whereHas('invoiceType',fn($q)=>$q->where('code','07'))->lockForUpdate()->first();

            if(!$series) throw new RuntimeException('Selecciona una serie activa de nota de crédito de la empresa emisora.');

            if($series->current_number < 1 || $series->current_number > ($series->max_number ?? 99999999)) throw new RuntimeException('La serie no dispone de correlativos.');

            $date=now('America/Lima');

            $xml=app(CreditNoteXml::class)->build($original,$series->series,$series->current_number,$reason,$description,$date,$company->document_number);

            $engine = app(CreditNoteAdjustment::class);
            // Credit eligibility comes from the signed source, never from a submitted flag.
            $sourceDom = new \DOMDocument(); $sourceDom->loadXML($original->xml_content, LIBXML_NONET);
            $sourceXp = new \DOMXPath($sourceDom); $sourceXp->registerNamespace('cac',CreditNoteXml::CAC); $sourceXp->registerNamespace('cbc',CreditNoteXml::CBC);
            $input['original_credit'] = $original->tipo_doc === '01' && $sourceXp->query('/*/cac:PaymentTerms[cbc:ID="FormaPago" and cbc:PaymentMeansID="Credito"]')->length > 0;
            if ($reason === '13' && (float)($input['pending_amount'] ?? 0) > $original->total) throw new RuntimeException('El saldo pendiente no puede superar el total de la factura.');
            $accepted = SunatInvoice::where('original_invoice_id',$original->id)->where('cdr_status','accepted')->get();
            $input['used_lines'] = [];
            foreach ($accepted as $prior) {
                foreach ($prior->note_adjustments['lines'] ?? [] as $lineId=>$priorLine) {
                    $input['used_lines'][$lineId]['net'] = ($input['used_lines'][$lineId]['net'] ?? 0) + (float)($priorLine['net'] ?? 0);
                    $input['used_lines'][$lineId]['quantity'] = ($input['used_lines'][$lineId]['quantity'] ?? 0) + (float)($priorLine['quantity'] ?? 0);
                }
            }
            $adjusted = $engine->adjust($xml,$reason,$input);
            $xml = $adjusted['xml']; $metadata = $adjusted['metadata'];
            $accepted = SunatInvoice::where('original_invoice_id',$original->id)->where('cdr_status','accepted')->get();
            if (CreditNoteReasons::cancelsDocument($reason) && $accepted->contains(fn($n)=>(float)$n->total>0)) throw new RuntimeException('Ya hay ajustes aceptados. Devuelve o ajusta únicamente el saldo por ítem.');
            $sourceLines = $engine->lines($original->xml_content);
            foreach ($metadata['lines'] as $id=>$row) {
                $usedNet=0; $usedQuantity=0;
                foreach($accepted as $prior) {
                    $priorLine=($prior->note_adjustments['lines'] ?? [])[$id] ?? [];
                    $usedNet+=(float)($priorLine['net'] ?? 0); $usedQuantity+=(float)($priorLine['quantity'] ?? 0);
                }
                if ($usedNet+(float)($row['net'] ?? 0)>$sourceLines[$id]['net']+0.001 || $usedQuantity+(float)($row['quantity'] ?? 0)>$sourceLines[$id]['quantity']+0.000001) throw new RuntimeException('El ajuste supera el saldo disponible del ítem '.$id.'.');
            }
            if ($reason === '07' && $original->order) {
                if ($original->isConsumptionSummary()) throw new RuntimeException('La devolución por ítem requiere un comprobante con detalle de productos, no un consumo resumido.');
                $items=$original->order->items->sortBy('id')->values();
                foreach ($metadata['lines'] as $id=>&$row) {
                    $position=array_search($id,array_keys($sourceLines)); $item=$items->get($position);
                    if (!$item || trim($item->product_name)!==trim($sourceLines[$id]['description']) || (float)$item->quantity !== (float)$sourceLines[$id]['quantity']) throw new RuntimeException('No se pudo vincular el ítem del XML al inventario original. Revisa el detalle antes de devolverlo.');
                    $row['order_item_id']=$item->id;
                }
                unset($row);
            }
            $totals = $this->xmlTotals($xml);

            if(SunatInvoice::where('seller_company_id',$company->id)->where('tipo_doc','07')->where('serie',$series->series)->where('correlativo',$series->current_number)->exists()) throw new RuntimeException('El correlativo ya existe. Corrige la configuración de la serie.');

            $note=SunatInvoice::create(array_merge($original->only(['seller_id','seller_company_id','pos_order_id','cliente_tipo_doc','cliente_num_doc','cliente_nombre','cliente_direccion','total_gravada','total_exonerada','total_inafecta','total_igv','total','moneda','detail_mode','consumption_description']),[

                'original_invoice_id'=>$original->id,'tipo_doc'=>'07','serie'=>$series->series,'correlativo'=>$series->current_number,

                'note_motivo'=>$reason,'note_description'=>trim($description),'note_affected_type'=>$original->tipo_doc,

                'fecha_emision'=>$date,'xml_content'=>$xml,'cdr_status'=>SunatInvoice::STATUS_GENERATED,
                'note_adjustments'=>$metadata,
                ...$totals,

            ]));

            $series->increment('current_number');

            return $note;

        });

    }



    public function submit(SunatInvoice $note): SunatInvoice

    {

        if(!$note->original_invoice_id) throw new RuntimeException('La nota no tiene comprobante original vinculado.');

        $note->refresh();

        if($note->cdr_status === SunatInvoice::STATUS_ACCEPTED) { $this->applyCancellation($note); return $note->refresh(); }

        if($note->cdr_status === SunatInvoice::STATUS_REJECTED) throw new RuntimeException('La nota fue rechazada. Corrige la causa y emite una nueva desde el comprobante original.');

        $claimed=SunatInvoice::whereKey($note->id)->whereNotIn('cdr_status',[SunatInvoice::STATUS_ACCEPTED,SunatInvoice::STATUS_REJECTED])

            ->where(fn($q)=>$q->whereNull('note_processing_at')->orWhere('note_processing_at','<',now()->subMinutes(5)))

            ->update(['note_processing_at'=>now()]);

        if(!$claimed) throw new RuntimeException('La nota ya está en proceso. Espera antes de consultar o reintentar.');

        try {

            $note->refresh();

            $service=app()->make(SunatService::class,['entity'=>$note->company]);

            // An uncertain attempt keeps its exact signed XML, date, number and reference.

            if($note->cdr_status === SunatInvoice::STATUS_ERROR && $note->company->sunat_env === 'production') {

                $status=$service->getCdrResult('07',$note->serie,$note->correlativo);

                if(in_array($status['status'] ?? '',[SunatInvoice::STATUS_ACCEPTED,SunatInvoice::STATUS_REJECTED])) {

                    $note->update(['cdr_status'=>$status['status'],'cdr_response'=>json_encode(['code'=>$status['code'],'description'=>$status['description'],'archivedCdr'=>base64_encode($status['cdr_zip'] ?? '')]),'errors'=>null]);

                }

            }

            if(!in_array($note->cdr_status,[SunatInvoice::STATUS_ACCEPTED,SunatInvoice::STATUS_REJECTED])) {

                $xml=(string)$note->xml_content;

                if(!str_contains($xml,'<ds:Signature')) {

                    $xml=$service->signCreditNote($xml);

                    $dom=new \DOMDocument(); $dom->loadXML($xml,LIBXML_NONET);

                    $digest=$dom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#','DigestValue')->item(0)?->textContent;

                    $note->update(['xml_content'=>$xml,'hash'=>$digest]);

                }

                $note->update($service->transmitCreditNote($xml,$note->serie,$note->correlativo));

            }

        } catch (\Throwable $e) {

            $note->refresh();

            if($note->cdr_status !== SunatInvoice::STATUS_ACCEPTED) $note->update(['cdr_status'=>SunatInvoice::STATUS_ERROR,'errors'=>json_encode([['message'=>$e->getMessage()]],JSON_UNESCAPED_UNICODE)]);

            throw $e;

        } finally { $note->update(['note_processing_at'=>null]); }

        if($note->cdr_status === SunatInvoice::STATUS_ACCEPTED) $this->applyCancellation($note);

        return $note->refresh();

    }



    public function applyCancellation(SunatInvoice $note): void

    {

        DB::transaction(function () use ($note) {

            $original=SunatInvoice::whereKey($note->original_invoice_id)->lockForUpdate()->firstOrFail();

            $note=SunatInvoice::whereKey($note->id)->lockForUpdate()->firstOrFail();

            if($note->cdr_status !== SunatInvoice::STATUS_ACCEPTED || $note->cancellation_applied_at) return;

            if($original->cancellation_applied_at) { $note->update(['cancellation_applied_at'=>now()]); return; }

            $label = $note->tipo_doc === 'RA' ? 'Comunicación de baja ' : 'Nota de crédito ';
            if ($note->tipo_doc === '07' && !in_array($note->note_motivo,['01','06'],true)) {
                $this->applyAdjustment($note,$original);
                return;
            }
            $order=PosOrder::whereKey($original->pos_order_id)->where('seller_id',$original->seller_id)->lockForUpdate()->first();

            if($order && $order->status !== 'cancelled') {

                app()->make(StockService::class, ['sellerId'=>(int)$original->seller_id])->restoreForOrder($order->items,$original->id);

                $sales=PosTransaction::where('pos_order_id',$order->id)->where('seller_id',$original->seller_id)->where('type','sale')->get();

                if($sales->isEmpty() && $order->paid_at && $order->payment_method !== 'credit') throw new RuntimeException('Nota aceptada; faltan los movimientos de pago originales para conciliar la devolución. Revisa la venta antes de reintentar.');

                $remaining=round($original->total,2);

                foreach($sales as $sale) {

                    if($sale->payment_method === 'credit' || $remaining <= 0) continue;

                    $amount=min($remaining,max(0,(float)$sale->amount)); if(!$amount)continue;

                    $originalSession=PosCashSession::whereKey($sale->cash_session_id)->where('seller_id',$original->seller_id)->first();

                    $session=$originalSession ? PosCashSession::where('pos_register_id',$originalSession->pos_register_id)->where('seller_id',$original->seller_id)->open()->lockForUpdate()->first() : null;

                    if(!$session) throw new RuntimeException('Nota aceptada; abre una sesión en la caja de la venta para conciliar la devolución y reintenta esta nota.');

                    PosTransaction::create(['cash_session_id'=>$session->id,'seller_id'=>$original->seller_id,'pos_order_id'=>$order->id,'type'=>'cash_out','amount'=>$amount,'description'=>$label.$note->serie.'-'.$note->correlativo,'payment_method'=>$sale->payment_method,'pos_bank_account_id'=>$sale->pos_bank_account_id]);

                    $session->increment('total_cash_out',$amount); $remaining=round($remaining-$amount,2);

                }

                $order->update(['status'=>'cancelled','cancelled_at'=>now(),'cancel_reason'=>$label.$note->serie.'-'.$note->correlativo.': '.$note->note_description]);

            }

            $original->update(['cdr_status'=>$note->tipo_doc === 'RA' ? SunatInvoice::STATUS_VOIDED : SunatInvoice::STATUS_CANCELLED,'cancellation_applied_at'=>now()]);

            $note->update(['cancellation_applied_at'=>now()]);

        });

    }


    private function xmlTotals(string $xml): array
    {
        $doc=new \DOMDocument();$doc->loadXML($xml,LIBXML_NONET);$xp=new \DOMXPath($doc);
        $xp->registerNamespace('cbc',CreditNoteXml::CBC);$xp->registerNamespace('cac',CreditNoteXml::CAC);
        $base=fn($code)=>(float)$xp->evaluate('sum(/*/cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="'.$code.'"]/cbc:TaxableAmount)');
        return ['total'=>(float)$xp->evaluate('string(/*/cac:LegalMonetaryTotal/cbc:PayableAmount)'),
            'total_gravada'=>$base('1000')+$base('1016'),'total_exonerada'=>$base('9997'),'total_inafecta'=>$base('9998'),
            'total_igv'=>(float)$xp->evaluate('sum(/*/cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000" or cac:TaxCategory/cac:TaxScheme/cbc:ID="1016"]/cbc:TaxAmount)')];
    }

    private function applyAdjustment(SunatInvoice $note, SunatInvoice $original): void
    {
        // Corrections do not return goods or reverse the sale's payment.
        if ($note->note_motivo === '02') {
            $original->update(['cdr_status'=>'cancelled','cancellation_applied_at'=>now()]);
        } elseif (!in_array($note->note_motivo,['03','13'],true)) {
            $order=PosOrder::whereKey($original->pos_order_id)->where('seller_id',$original->seller_id)->lockForUpdate()->first();
            if ($order) {
                if ($note->note_motivo === '07') {
                    $items=collect();
                    foreach($note->note_adjustments['lines'] ?? [] as $row) {
                        $item=$order->items->firstWhere('id',$row['order_item_id'] ?? 0);
                        if (!$item) throw new RuntimeException('Nota aceptada: falta el vínculo de inventario de un ítem.');
                        $copy=clone $item;$copy->quantity=$row['quantity'];$items->push($copy);
                    }
                    app()->make(StockService::class,['sellerId'=>(int)$original->seller_id])->restoreForOrder($items,$note->id);
                }
                $previousCredit=SunatInvoice::where('original_invoice_id',$original->id)->where('cdr_status','accepted')->where('id','!=',$note->id)->whereNotIn('note_motivo',['02','03','13'])->sum('total');
                $paid=PosTransaction::where('pos_order_id',$order->id)->where('seller_id',$original->seller_id)->where('type','sale')->where('payment_method','!=','credit')->sum('amount');
                $returned=PosTransaction::where('pos_order_id',$order->id)->where('seller_id',$original->seller_id)->where('type','cash_out')->sum('amount');
                $outstanding=max(0,$original->total-$previousCredit-max(0,$paid-$returned));
                $remaining=round(max(0,$note->total-$outstanding),2);
                $sales=PosTransaction::where('pos_order_id',$order->id)->where('seller_id',$original->seller_id)->where('type','sale')->orderBy('id')->get();
                foreach($sales->groupBy(fn($sale)=>$sale->payment_method.'|'.$sale->pos_bank_account_id) as $group) {
                    $sale=$group->first();if($sale->payment_method === 'credit')continue;
                    $refunded=PosTransaction::where('pos_order_id',$order->id)->where('seller_id',$original->seller_id)->where('type','cash_out')->where('payment_method',$sale->payment_method)->where('pos_bank_account_id',$sale->pos_bank_account_id)->sum('amount');
                    $amount=min($remaining,max(0,$group->sum('amount')-$refunded));if($amount<=0)continue;
                    $oldSession=PosCashSession::whereKey($sale->cash_session_id)->where('seller_id',$original->seller_id)->first();
                    $session=$oldSession ? PosCashSession::where('pos_register_id',$oldSession->pos_register_id)->where('seller_id',$original->seller_id)->open()->lockForUpdate()->first() : null;
                    if(!$session)throw new RuntimeException('Nota aceptada: abre la caja de la venta para conciliar el ajuste y reintenta.');
                    PosTransaction::create(['cash_session_id'=>$session->id,'seller_id'=>$original->seller_id,'pos_order_id'=>$order->id,'type'=>'cash_out','amount'=>$amount,'payment_method'=>$sale->payment_method,'pos_bank_account_id'=>$sale->pos_bank_account_id,'description'=>'Nota de crédito '.$note->serie.'-'.$note->correlativo]);
                    $session->increment('total_cash_out',$amount);$remaining=round($remaining-$amount,2);
                }
            }
        }
        $note->update(['cancellation_applied_at'=>now()]);
    }
}

