@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-truck-loading"></i></span> Recepciones de Mercadería
@endsection

@section('seller-content')
<div class="s-content">

    <div class="s-card" style="margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px">
            <div>
                <h3 class="s-card-title" style="margin:0"><i class="las la-plus-circle"></i> Nueva Recepción de Mercadería</h3>
                <p style="font-size:12px;color:var(--s-text-3);margin:5px 0 0">
                    Registre ingresos de productos a almacenes desde una Orden de Compra aprobada o directamente.
                </p>
            </div>
            <div style="display:flex;gap:10px">
                <a class="s-btn s-btn-outline" href="{{ route('seller.logistics.receptions.create') }}">
                    <i class="las la-plus"></i> Recepción Directa (Sin OC)
                </a>
            </div>
        </div>

        @if(count($pendingOC) > 0)
        <hr class="s-divider">
        <h4 style="margin:0 0 10px;color:var(--s-text-2);font-size:13px">Órdenes de Compra por Recibir:</h4>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            @foreach($pendingOC as $oc)
            <div style="background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:15px">
                <div>
                    <span style="font-weight:700;font-size:13px;color:var(--s-text)">{{ $oc->order_number }}</span>
                    <br><small style="color:var(--s-text-3)">{{ $oc->supplier->name }}</small>
                </div>
                <a class="s-btn s-btn-primary s-btn-xs" href="{{ route('seller.logistics.receptions.create', $oc->id) }}">
                    <i class="las la-dolly"></i> Recibir
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- LISTADO -->
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-history"></i> Historial de Recepciones</h3>
        <div class="s-table-responsive">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Nº Recepción</th>
                        <th>Orden de Compra</th>
                        <th>Almacén Destino</th>
                        <th>Fecha Recepción</th>
                        <th>Documento Referencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receptions as $r)
                    <tr>
                        <td><b>#{{ $r->id }}</b></td>
                        <td>
                            @if($r->purchaseOrder)
                            <span class="s-badge s-badge-blue">{{ $r->purchaseOrder->order_number }}</span>
                            @else
                            <span class="s-badge s-badge-gray">Directa</span>
                            @endif
                        </td>
                        <td>{{ $r->warehouse->name }}</td>
                        <td>{{ $r->reception_date->format('d/m/Y') }}</td>
                        <td>
                            @if($r->document_number)
                            <span class="s-badge s-badge-gray">
                                {{ $r->document_type === '09' ? 'Guía' : 'Factura' }}: {{ $r->document_number }}
                            </span>
                            @else
                            —
                            @endif
                        </td>
                        <td>
                            <span class="s-badge s-badge-green">Completado</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:30px">
                            Sin recepciones de mercadería registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:15px">
            {{ $receptions->links() }}
        </div>
    </div>

</div>
@endsection
