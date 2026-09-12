<?php

namespace Tests\Feature;



use Tests\TestCase;

use App\Models\{SunatInvoice, SellerCompany, PosInvoiceType, PosInvoiceSeries};

use App\Services\{CreditNoteCancellation, CreditNoteXml, SunatService};

use Illuminate\Support\Facades\{DB, Schema};

use Illuminate\Database\Schema\Blueprint;



class CreditNoteCancellationTest extends TestCase

{

    protected function setUp(): void

    {

        parent::setUp();

        // Dedicated memory database: never migrate, truncate or transact against the application's database.

        config(['database.default'=>'credit_note_test', 'database.connections.credit_note_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]]);

        DB::purge('credit_note_test');

        Schema::create('sunat_invoices', function(Blueprint $t) {

            $t->id(); $t->timestamps();

            foreach(['seller_id','seller_company_id','pos_order_id','original_invoice_id','correlativo'] as $c) $t->integer($c)->nullable();

            foreach(['tipo_doc','serie','cdr_status','xml_content','moneda','note_motivo','note_description','note_affected_type','fecha_emision','cdr_response','sunat_response','errors','hash','note_processing_at','cancellation_applied_at','ticket','cliente_tipo_doc','cliente_num_doc','cliente_nombre','cliente_direccion','note_adjustments'] as $c) $t->text($c)->nullable();

            foreach(['total','total_gravada','total_exonerada','total_inafecta','total_igv'] as $c) $t->decimal($c,12,2)->nullable();

        });

        Schema::create('stores', function(Blueprint $t) {$t->id();$t->integer('seller_id');});

        Schema::create('seller_companies', function(Blueprint $t) {$t->id();$t->timestamps();$t->integer('seller_id');$t->string('document_number');$t->string('sunat_env');});

        Schema::create('pos_invoice_types', function(Blueprint $t) {$t->id();$t->timestamps();$t->string('code');});

        Schema::create('pos_invoice_series', function(Blueprint $t) {$t->id();$t->timestamps();$t->integer('seller_company_id');$t->integer('invoice_type_id');$t->string('series');$t->integer('current_number');$t->integer('max_number');$t->boolean('active');});

        Schema::create('pos_orders', function(Blueprint $t) {$t->id();$t->timestamps();$t->integer('seller_id');$t->string('status');$t->text('cancel_reason')->nullable();$t->timestamp('cancelled_at')->nullable();$t->softDeletes();});

        Schema::create('pos_order_items', function(Blueprint $t) {$t->id();$t->integer('pos_order_id');});

        Schema::create('pos_cash_sessions', function(Blueprint $t) {$t->id();$t->timestamps();$t->integer('seller_id');$t->integer('pos_register_id');$t->string('status');$t->decimal('total_cash_out',12,2)->default(0);});

        Schema::create('pos_transactions', function(Blueprint $t) {$t->id();$t->timestamps();foreach(['seller_id','pos_order_id','cash_session_id','pos_bank_account_id'] as $c)$t->integer($c)->nullable();foreach(['type','payment_method','description'] as $c)$t->text($c)->nullable();$t->decimal('amount',12,2);});

    }

    private function source(string $type='03'): array

    {

        $company=SellerCompany::create(['seller_id'=>1,'document_number'=>'20123456789','sunat_env'=>'beta']);

        $kind=PosInvoiceType::create(['code'=>'07']);

        $series=PosInvoiceSeries::create(['seller_company_id'=>$company->id,'invoice_type_id'=>$kind->id,'series'=>$type==='03'?'BC01':'FC01','current_number'=>1,'max_number'=>99999999,'active'=>true]);

        $id=$type==='03'?'B001':'F001';

        $xml='<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cbc="'.CreditNoteXml::CBC.'" xmlns:cac="'.CreditNoteXml::CAC.'"><cbc:UBLVersionID>2.1</cbc:UBLVersionID><cbc:ID>'.$id.'-239</cbc:ID><cbc:IssueDate>2026-01-01</cbc:IssueDate><cbc:InvoiceTypeCode>'.$type.'</cbc:InvoiceTypeCode><cbc:DocumentCurrencyCode>USD</cbc:DocumentCurrencyCode><cac:Signature><cbc:ID>TEST</cbc:ID></cac:Signature><cac:AccountingSupplierParty><cac:Party><cac:PartyIdentification><cbc:ID>20123456789</cbc:ID></cac:PartyIdentification></cac:Party></cac:AccountingSupplierParty><cac:AccountingCustomerParty><cac:Party><cac:PartyIdentification><cbc:ID schemeID="1">12345678</cbc:ID></cac:PartyIdentification></cac:Party></cac:AccountingCustomerParty><cac:TaxTotal><cbc:TaxAmount currencyID="USD">10.00</cbc:TaxAmount><cac:TaxSubtotal><cbc:TaxableAmount currencyID="USD">100.00</cbc:TaxableAmount><cbc:TaxAmount currencyID="USD">10.00</cbc:TaxAmount><cac:TaxCategory><cbc:Percent>10</cbc:Percent><cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory></cac:TaxSubtotal></cac:TaxTotal><cac:LegalMonetaryTotal><cbc:PayableAmount currencyID="USD">110.00</cbc:PayableAmount></cac:LegalMonetaryTotal><cac:InvoiceLine><cbc:ID>1</cbc:ID><cbc:InvoicedQuantity unitCode="NIU">2</cbc:InvoicedQuantity><cbc:LineExtensionAmount currencyID="USD">100.00</cbc:LineExtensionAmount><cac:TaxTotal><cbc:TaxAmount currencyID="USD">10.00</cbc:TaxAmount><cac:TaxSubtotal><cbc:TaxableAmount currencyID="USD">100.00</cbc:TaxableAmount><cbc:TaxAmount currencyID="USD">10.00</cbc:TaxAmount><cac:TaxCategory><cbc:Percent>10</cbc:Percent><cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory></cac:TaxSubtotal></cac:TaxTotal><cac:Item><cbc:Description>Producto original</cbc:Description></cac:Item><cac:Price><cbc:PriceAmount currencyID="USD">50.00</cbc:PriceAmount></cac:Price></cac:InvoiceLine></Invoice>';

        $source=SunatInvoice::create(['seller_id'=>1,'seller_company_id'=>$company->id,'tipo_doc'=>$type,'serie'=>$id,'correlativo'=>239,'cdr_status'=>'accepted','total'=>110,'moneda'=>'USD','xml_content'=>$xml]);

        return [$source,$series];

    }

    public function test_boleta_preserves_original_values_and_reuses_reservation(): void

    {

        [$source,$series]=$this->source(); $service=app(CreditNoteCancellation::class);

        $note=$service->reserve($source,$series->id,'01','Anulación total');

        $again=$service->reserve($source,$series->id,'01','Otro clic');

        $this->assertSame($note->id,$again->id);$this->assertSame(2,$series->fresh()->current_number);

        $this->assertStringContainsString('<cbc:ReferenceID>B001-239</cbc:ReferenceID>',$note->xml_content);

        $this->assertStringContainsString('<cbc:CreditedQuantity unitCode="NIU">2</cbc:CreditedQuantity>',$note->xml_content);

        $this->assertStringContainsString('currencyID="USD">10.00',$note->xml_content);

        $this->assertSame('accepted',$source->fresh()->cdr_status);

    }

    public function test_factura_uses_fc_and_rejects_bc_without_consuming_number(): void

    {

        [$source,$series]=$this->source('01');$series->update(['series'=>'BC01']);

        try {app(CreditNoteCancellation::class)->reserve($source,$series->id,'01','Anulación');$this->fail('BC accepted for factura');}

        catch(\InvalidArgumentException $e) {$this->assertSame(1,$series->fresh()->current_number);}

        $series->update(['series'=>'FC01']);$note=app(CreditNoteCancellation::class)->reserve($source,$series->id,'06','Devolución total');

        $this->assertSame('FC01',$note->serie);$this->assertStringContainsString('<cbc:DocumentTypeCode>01</cbc:DocumentTypeCode>',$note->xml_content);

    }

    public function test_error_retry_keeps_number_and_only_acceptance_cancels(): void

    {

        [$source,$series]=$this->source();$service=app(CreditNoteCancellation::class);

        $note=$service->reserve($source,$series->id,'01','Anulación');

        $mock=\Mockery::mock(SunatService::class);

        $mock->shouldReceive('signCreditNote')->andReturnUsing(fn($xml)=>$xml);

        $mock->shouldReceive('transmitCreditNote')->once()->andReturn(['cdr_status'=>'error']);

        $mock->shouldReceive('transmitCreditNote')->once()->andReturn(['cdr_status'=>'accepted']);

        $this->app->bind(SunatService::class,fn()=>$mock);

        $this->assertSame('error',$service->submit($note)->cdr_status);$this->assertSame('accepted',$source->fresh()->cdr_status);

        $service->submit($note);$service->submit($note);

        $this->assertSame('cancelled',$source->fresh()->cdr_status);$this->assertNotNull($note->fresh()->cancellation_applied_at);

        $this->assertSame(2,$series->fresh()->current_number);

    }

    public function test_rejection_does_not_cancel_original(): void

    {

        [$source,$series]=$this->source();$service=app(CreditNoteCancellation::class);$note=$service->reserve($source,$series->id,'01','Anulación');

        $mock=\Mockery::mock(SunatService::class);$mock->shouldReceive('signCreditNote')->andReturnUsing(fn($xml)=>$xml);$mock->shouldReceive('transmitCreditNote')->once()->andReturn(['cdr_status'=>'rejected']);$this->app->bind(SunatService::class,fn()=>$mock);

        $service->submit($note);$this->assertSame('accepted',$source->fresh()->cdr_status);$this->assertNull($note->fresh()->cancellation_applied_at);

    }

    public function test_other_issuer_series_is_rejected(): void

    {

        [$source,$series]=$this->source();$series->update(['seller_company_id'=>99]);$this->expectException(\RuntimeException::class);app(CreditNoteCancellation::class)->reserve($source,$series->id,'01','Anulación');

    }



    public function test_accepted_note_refunds_original_methods_once_in_open_session(): void

    {

        [$source,$series]=$this->source();

        $order=\App\Models\PosOrder::create(['seller_id'=>1,'status'=>'paid']);$source->update(['pos_order_id'=>$order->id]);

        $closed=\App\Models\PosCashSession::create(['seller_id'=>1,'pos_register_id'=>2,'status'=>'closed']);

        $open=\App\Models\PosCashSession::create(['seller_id'=>1,'pos_register_id'=>2,'status'=>'open']);

        foreach(['cash'=>40,'card'=>70] as $method=>$amount) \App\Models\PosTransaction::create(['seller_id'=>1,'pos_order_id'=>$order->id,'cash_session_id'=>$closed->id,'type'=>'sale','payment_method'=>$method,'amount'=>$amount]);

        $stock=\Mockery::mock(\App\Services\StockService::class);$stock->shouldReceive('restoreForOrder')->once()->andReturn([]);$this->app->bind(\App\Services\StockService::class,fn()=>$stock);

        $service=app(CreditNoteCancellation::class);$note=$service->reserve($source,$series->id,'01','Anulación');$note->update(['cdr_status'=>'accepted']);

        $service->applyCancellation($note);$service->applyCancellation($note);

        $this->assertSame('cancelled',$order->fresh()->status);

        $this->assertSame(0.0,$closed->fresh()->total_cash_out);$this->assertSame(110.0,$open->fresh()->total_cash_out);

        $refunds=\App\Models\PosTransaction::where('type','cash_out')->get();$this->assertCount(2,$refunds);$this->assertSame(['cash','card'],$refunds->pluck('payment_method')->all());

    }



    public function test_inflight_note_cannot_be_sent_twice(): void

    {

        [$source,$series]=$this->source();$service=app(CreditNoteCancellation::class);

        $note=$service->reserve($source,$series->id,'01','Anulación');$note->update(['note_processing_at'=>now()]);

        $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('ya está en proceso');$service->submit($note);

    }

    public function test_mismatched_original_total_does_not_consume_number(): void

    {

        [$source,$series]=$this->source();$source->update(['total'=>120]);

        try {app(CreditNoteCancellation::class)->reserve($source,$series->id,'01','Anulación');$this->fail('Mismatched total accepted');}

        catch(\InvalidArgumentException $e) {$this->assertSame(1,$series->fresh()->current_number);$this->assertSame(1,SunatInvoice::count());}

    }


    public function test_ra_pending_ticket_is_reused_then_acceptance_applies_once(): void
    {
        [$source,$series]=$this->source('01');$source->update(['fecha_emision'=>now('America/Lima')]);
        $mock=\Mockery::mock(SunatService::class);
        $mock->shouldReceive('buildVoidedXml')->once()->andReturn('<VoidedDocuments/>');
        $mock->shouldReceive('transmitVoidedXml')->once()->andReturn(['ticket'=>'TICKET-1','cdr_status'=>'pending']);
        $mock->shouldReceive('consultVoidedTicket')->with('TICKET-1')->once()->andReturn(['cdr_status'=>'pending']);
        $mock->shouldReceive('consultVoidedTicket')->with('TICKET-1')->once()->andReturn(['cdr_status'=>'accepted']);
        $this->app->bind(SunatService::class,fn()=>$mock);
        $service=app(\App\Services\VoidedCancellation::class);
        $ra=$service->reserve($source,'Documento no otorgado',true);
        $this->assertSame($ra->id,$service->reserve($source,'Segundo clic',true)->id);
        $this->assertSame('pending',$service->submit($ra)->cdr_status);
        $this->assertSame('accepted',$source->fresh()->cdr_status);
        $service->submit($ra);$service->submit($ra);
        $this->assertSame('voided',$source->fresh()->cdr_status);
        $this->assertSame('TICKET-1',$ra->fresh()->ticket);
    }
    public function test_ra_rejection_preserves_factura(): void
    {
        [$source]=$this->source('01');$source->update(['fecha_emision'=>now('America/Lima')]);
        $mock=\Mockery::mock(SunatService::class);$mock->shouldReceive('buildVoidedXml')->andReturn('<VoidedDocuments/>');
        $mock->shouldReceive('transmitVoidedXml')->andReturn(['ticket'=>'TICKET-2','cdr_status'=>'pending']);
        $mock->shouldReceive('consultVoidedTicket')->andReturn(['cdr_status'=>'rejected']);$this->app->bind(SunatService::class,fn()=>$mock);
        $service=app(\App\Services\VoidedCancellation::class);$ra=$service->reserve($source,'No otorgado',true);$service->submit($ra);
        $this->assertSame('accepted',$source->fresh()->cdr_status);$this->assertNull($ra->fresh()->cancellation_applied_at);
    }
    public function test_ra_deadline_includes_seventh_day_and_rejects_eighth(): void
    {
        [$source]=$this->source('01');$service=app(\App\Services\VoidedCancellation::class);
        $source->fecha_emision=now('America/Lima')->subDays(7);$this->assertNull($service->eligibilityError($source));
        $source->fecha_emision=now('America/Lima')->subDays(8);$this->assertNotNull($service->eligibilityError($source));
        $source->tipo_doc='03';$this->assertNotNull($service->eligibilityError($source));
    }
    public function test_ra_requires_non_delivery_declaration(): void
    {
        [$source]=$this->source('01');$source->update(['fecha_emision'=>now('America/Lima')]);
        $this->expectException(\RuntimeException::class);app(\App\Services\VoidedCancellation::class)->reserve($source,'Motivo',false);
    }
    public function test_credit_note_cannot_bypass_pending_ra(): void
    {
        [$source,$series]=$this->source('01');
        SunatInvoice::create(['original_invoice_id'=>$source->id,'tipo_doc'=>'RA','cdr_status'=>'pending']);
        $this->expectException(\RuntimeException::class);app(CreditNoteCancellation::class)->reserve($source,$series->id,'01','Anulación');
    }
    public function test_ra_consultation_failure_keeps_ticket_for_next_consultation(): void
    {
        [$source]=$this->source('01');$source->update(['fecha_emision'=>now('America/Lima')]);
        $mock=\Mockery::mock(SunatService::class);$mock->shouldReceive('buildVoidedXml')->andReturn('<VoidedDocuments/>');
        $mock->shouldReceive('transmitVoidedXml')->once()->andReturn(['ticket'=>'KEEP','cdr_status'=>'pending']);
        $mock->shouldReceive('consultVoidedTicket')->once()->andThrow(new \RuntimeException('Timeout'));
        $mock->shouldReceive('consultVoidedTicket')->once()->andReturn(['cdr_status'=>'pending']);$this->app->bind(SunatService::class,fn()=>$mock);
        $service=app(\App\Services\VoidedCancellation::class);$ra=$service->reserve($source,'No otorgado',true);
        try {$service->submit($ra);$this->fail('Missing timeout');} catch(\RuntimeException $e) {$this->assertSame('Timeout',$e->getMessage());}
        $this->assertSame('KEEP',$ra->fresh()->ticket);$service->submit($ra);$this->assertSame('accepted',$source->fresh()->cdr_status);
    }

    public function test_baja_modal_has_separate_route_and_declaration(): void
    {
        [$source]=$this->source('01');
        $html=view('seller.partials.invoice_baja',['invoice'=>$source,'raEligibilityError'=>null])->render();
        $this->assertStringContainsString('/baja', $html);
        $this->assertStringContainsString('name="not_granted"', $html);
        $this->assertStringContainsString('Solicitar baja RA', $html);
        $disabled=view('seller.partials.invoice_baja',['invoice'=>$source,'raEligibilityError'=>'Fuera de plazo'])->render();
        $this->assertStringContainsString('disabled', $disabled);
    }

    public function test_sunat_ra_status_adapter_handles_98_and_archives_cdr(): void
    {
        $service=\Mockery::mock(SunatService::class)->makePartial();
        $pending=(new \Greenter\Model\Response\StatusResult())->setCode('98');
        $service->shouldReceive('getStatus')->with('WAIT')->andReturn($pending);
        $this->assertSame('pending',$service->consultVoidedTicket('WAIT')['cdr_status']);
        $cdr=(new \Greenter\Model\Response\CdrResponse())->setCode('0')->setDescription('Aceptada');
        $accepted=(new \Greenter\Model\Response\StatusResult())->setCode('0')->setSuccess(true)->setCdrResponse($cdr)->setCdrZip('test-zip');
        $service->shouldReceive('getStatus')->with('OK')->andReturn($accepted);
        $data=$service->consultVoidedTicket('OK');$this->assertSame('accepted',$data['cdr_status']);
        $this->assertSame('test-zip',base64_decode(json_decode($data['cdr_response'],true)['archivedCdr']));
    }

    public function test_invoice_detail_body_renders_factura_and_ra(): void
    {
        [$invoice]=$this->source('01');
        $invoice->forceFill(['total_gravada'=>100,'total_igv'=>10,'total_exonerada'=>0,'total_inafecta'=>0]);
        $template=str_replace("@extends('seller.layouts.app')", '', file_get_contents(resource_path('views/seller/invoice_detail.blade.php')))."@yield('seller-content')";
        $data=['invoice'=>$invoice,'pageTitle'=>'Comprobante','creditNoteSeries'=>collect(),'cancellationNotes'=>collect(),'raEligibilityError'=>null,'xmlFormatted'=>null,'cdrData'=>null,'sunatResponse'=>null,'errors'=>null];
        $html=\Illuminate\Support\Facades\Blade::render($template,$data);
        $this->assertStringContainsString('Solicitar comunicación de baja (RA)',$html);
        $invoice->tipo_doc='RA';$invoice->original_invoice_id=1;$invoice->ticket='TICKET';$invoice->cdr_status='pending';
        $html=\Illuminate\Support\Facades\Blade::render($template,$data);
        $this->assertStringContainsString('Consultar ticket / completar baja',$html);
    }

    public function test_baja_modal_without_eligibility_does_not_break_detail_or_allow_submission(): void
    {
        [$source]=$this->source('01');
        $html=view('seller.partials.invoice_baja',['invoice'=>$source])->render();
        $this->assertStringContainsString('La validación de baja no está disponible', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*disabled/', $html);
    }

    public function test_baja_modal_without_registered_route_keeps_detail_available(): void
    {
        [$source]=$this->source('01');
        $routes=app('router')->getRoutes();
        $withoutBaja=new \Illuminate\Routing\RouteCollection();
        foreach($routes as $route) {
            if($route->getName() !== 'seller.invoice.baja') $withoutBaja->add($route);
        }
        app('router')->setRoutes($withoutBaja);
        app('url')->setRoutes($withoutBaja);
        try {
            $html=view('seller.partials.invoice_baja',['invoice'=>$source,'raEligibilityError'=>null])->render();
            $this->assertStringContainsString('La ruta de baja no está disponible', $html);
            $this->assertStringNotContainsString('<form', $html);
        } finally {
            app('router')->setRoutes($routes);
            app('url')->setRoutes($routes);
        }
    }

    public function test_all_thirteen_reasons_generate_their_respective_adjustments(): void
    {
        $engine=app(\App\Services\CreditNoteAdjustment::class);
        foreach(array_keys(\App\Services\CreditNoteReasons::LABELS) as $key) {
            $reason=(string)$key;[$source,$series]=$this->source('01');
            if($reason==='11')$source->xml_content=str_replace(['1000','IGV','>10.00<','>10</cbc:Percent>'],['9995','EXP','>0.00<','>0</cbc:Percent>'],$source->xml_content);
            if($reason==='12')$source->xml_content=str_replace(['1000','IGV'],['1016','IVAP'],$source->xml_content);
            // Keep source totals in agreement when changing this fixture to export.
            if($reason==='11') {$source->xml_content=str_replace('>110.00<','>100.00<',$source->xml_content);$source->total=100;}
            $base=app(CreditNoteXml::class)->build($source,'FC01',1,$reason,'Prueba motivo '. $reason,now('America/Lima'),'20123456789');
            $input=['correct_ruc'=>'20100070970','global_amount'=>'20.00','lines'=>[1=>['description'=>'Descripción corregida','amount'=>'20.00','quantity'=>'1']],
                'original_credit'=>true,'pending_amount'=>'50.00','installments'=>[['amount'=>'20.00','date'=>now('America/Lima')->addDays(5)->format('Y-m-d')],['amount'=>'30.00','date'=>now('America/Lima')->addDays(10)->format('Y-m-d')]]];
            $result=$engine->adjust($base,$reason,$input);
            $this->assertStringContainsString('<cbc:ResponseCode>'.$reason.'</cbc:ResponseCode>',$result['xml']);
            $doc=new \DOMDocument();$doc->loadXML($result['xml']);$xp=new \DOMXPath($doc);$xp->registerNamespace('cac',CreditNoteXml::CAC);$xp->registerNamespace('cbc',CreditNoteXml::CBC);
            $amount=(float)$xp->evaluate('string(/*/cac:LegalMonetaryTotal/cbc:PayableAmount)');
            $expected=match($reason){'01','02','06'=>110.0,'03','13'=>0.0,'07'=>55.0,'11'=>20.0,default=>22.0};
            $this->assertSame($expected,$amount,'Motivo '.$reason);
            if($reason==='03')$this->assertStringContainsString('Descripción corregida',$result['xml']);
            if($reason==='13')$this->assertSame(3,$xp->query('/*/cac:PaymentTerms')->length);
        }
    }
    public function test_partial_adjustment_leaves_original_valid_and_caps_cumulative_amounts(): void
    {
        [$source,$series]=$this->source();$service=app(CreditNoteCancellation::class);
        $note=$service->reserve($source,$series->id,'05','Descuento',['lines'=>[1=>['amount'=>'60.00']]]);
        $this->assertSame(66.0,$note->total);$note->update(['cdr_status'=>'accepted']);$service->applyCancellation($note);
        $this->assertSame('accepted',$source->fresh()->cdr_status);
        $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('saldo disponible');
        $service->reserve($source,$series->id,'05','Otro descuento',['lines'=>[1=>['amount'=>'50.00']]]);
    }
    public function test_installment_correction_cannot_be_requested_for_cash_sale(): void
    {
        [$source,$series]=$this->source('01');$this->expectException(\InvalidArgumentException::class);
        app(CreditNoteCancellation::class)->reserve($source,$series->id,'13','Cuotas',['original_credit'=>true,'pending_amount'=>'10.00','installments'=>[['amount'=>'10.00','date'=>now()->addDay()->format('Y-m-d')]]]);
    }
}

