@if($invoice->tipo_doc === '07' && $invoice->original_invoice_id)
<div style="margin:12px 0; padding:10px; border:1px solid #ddd; font-size:12px;">
    <strong>Motivo {{ $invoice->note_motivo }}: {{ \App\Services\CreditNoteReasons::LABELS[$invoice->note_motivo] ?? $invoice->note_motivo }}</strong>
    @if($invoice->note_motivo === '02' && !empty($invoice->note_adjustments['correct_ruc']))
        <p>RUC correcto para el comprobante de reemplazo: {{ $invoice->note_adjustments['correct_ruc'] }}. La nota se refiere al receptor original.</p>
    @endif
    @if($invoice->note_motivo === '13')
        <p>Monto pendiente corregido: {{ $invoice->moneda }} {{ number_format($invoice->note_adjustments['pending_amount'] ?? 0, 2) }}</p>
        <table style="width:100%; text-align:left;">
            <thead><tr><th>Cuota</th><th>Vencimiento</th><th>Importe</th></tr></thead>
            <tbody>
            @foreach($invoice->note_adjustments['installments'] ?? [] as $i=>$installment)
            <tr><td>{{ $i+1 }}</td><td>{{ $installment['date'] }}</td><td>{{ $invoice->moneda }} {{ number_format($installment['amount'],2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif
