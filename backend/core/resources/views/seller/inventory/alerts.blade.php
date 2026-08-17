@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-exclamation-triangle"></i></span>
Alertas de Stock
@endsection

@section('seller-content')
<div class="s-content">

{{-- Summary Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:24px;">
    <div class="s-card" style="border-left:4px solid #ef4444;">
        <div style="font-size:12px;color:var(--s-text-3);">Sin Stock</div>
        <div style="font-size:28px;font-weight:900;color:#ef4444;">{{ $outOfStockItems->count() }}</div>
        <div style="font-size:11px;color:var(--s-text-3);">items con stock agotado</div>
    </div>
    <div class="s-card" style="border-left:4px solid #f59e0b;">
        <div style="font-size:12px;color:var(--s-text-3);">Stock Bajo</div>
        <div style="font-size:28px;font-weight:900;color:#f59e0b;">{{ $lowStockItems->count() }}</div>
        <div style="font-size:11px;color:var(--s-text-3);">items por debajo del mínimo</div>
    </div>
    <div class="s-card" style="border-left:4px solid #3b82f6;">
        <div style="font-size:12px;color:var(--s-text-3);">Sobrestock</div>
        <div style="font-size:28px;font-weight:900;color:#3b82f6;">{{ $overStockItems->count() }}</div>
        <div style="font-size:11px;color:var(--s-text-3);">items por encima del máximo</div>
    </div>
    <div class="s-card" style="border-left:4px solid var(--s-accent);">
        <div style="font-size:12px;color:var(--s-text-3);">Valor Total Inventario</div>
        <div style="font-size:28px;font-weight:900;color:var(--s-accent);">S/ {{ number_format($totalValue, 2) }}</div>
        <div style="font-size:11px;color:var(--s-text-3);">costo de stock actual</div>
    </div>
</div>

{{-- Out of Stock --}}
@if($outOfStockItems->count())
<div class="s-card" style="margin-bottom:20px;border-left:4px solid #ef4444;">
    <h3 class="s-card-title" style="color:#ef4444;"><i class="las la-times-circle"></i> Sin Stock ({{ $outOfStockItems->count() }})</h3>
    <div style="overflow-x:auto;">
    <table class="s-table" style="font-size:13px;">
        <thead><tr>
            <th>Código</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>Mínimo</th><th>Costo</th>
        </tr></thead>
        <tbody>
        @foreach($outOfStockItems as $item)
        <tr>
            <td style="color:var(--s-text-3);">{{ $item->code ?? '—' }}</td>
            <td><b>{{ $item->name }}</b></td>
            <td>{{ $item->category ?? '—' }}</td>
            <td style="color:#ef4444;font-weight:700;">{{ number_format($item->stock, 2) }}</td>
            <td>{{ number_format($item->min_stock, 2) }}</td>
            <td>S/ {{ number_format($item->cost, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Low Stock --}}
@if($lowStockItems->count())
<div class="s-card" style="margin-bottom:20px;border-left:4px solid #f59e0b;">
    <h3 class="s-card-title" style="color:#f59e0b;"><i class="las la-exclamation-triangle"></i> Stock Bajo ({{ $lowStockItems->count() }})</h3>
    <div style="overflow-x:auto;">
    <table class="s-table" style="font-size:13px;">
        <thead><tr>
            <th>Código</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>Mínimo</th><th>% Restante</th><th>Costo</th>
        </tr></thead>
        <tbody>
        @foreach($lowStockItems as $item)
        @php $pct = $item->min_stock > 0 ? round($item->stock / $item->min_stock * 100) : 0; @endphp
        <tr>
            <td style="color:var(--s-text-3);">{{ $item->code ?? '—' }}</td>
            <td><b>{{ $item->name }}</b></td>
            <td>{{ $item->category ?? '—' }}</td>
            <td style="color:#f59e0b;font-weight:700;">{{ number_format($item->stock, 2) }}</td>
            <td>{{ number_format($item->min_stock, 2) }}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;height:6px;background:var(--s-bg-3);border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ min(100, $pct) }}%;background:{{ $pct > 50 ? '#f59e0b' : '#ef4444' }};border-radius:3px;"></div>
                    </div>
                    <span style="font-size:11px;font-weight:600;min-width:35px;text-align:right;">{{ $pct }}%</span>
                </div>
            </td>
            <td>S/ {{ number_format($item->cost, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Over Stock --}}
@if($overStockItems->count())
<div class="s-card" style="margin-bottom:20px;border-left:4px solid #3b82f6;">
    <h3 class="s-card-title" style="color:#3b82f6;"><i class="las la-arrow-up"></i> Sobrestock ({{ $overStockItems->count() }})</h3>
    <div style="overflow-x:auto;">
    <table class="s-table" style="font-size:13px;">
        <thead><tr>
            <th>Código</th><th>Nombre</th><th>Stock</th><th>Máximo</th><th>Excedente</th><th>Costo Excedente</th>
        </tr></thead>
        <tbody>
        @foreach($overStockItems as $item)
        @php $excess = $item->stock - $item->max_stock; @endphp
        <tr>
            <td style="color:var(--s-text-3);">{{ $item->code ?? '—' }}</td>
            <td><b>{{ $item->name }}</b></td>
            <td style="color:#3b82f6;font-weight:700;">{{ number_format($item->stock, 2) }}</td>
            <td>{{ number_format($item->max_stock, 2) }}</td>
            <td>+{{ number_format($excess, 2) }}</td>
            <td>S/ {{ number_format($excess * $item->cost, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@if($outOfStockItems->count() === 0 && $lowStockItems->count() === 0 && $overStockItems->count() === 0)
<div class="s-card" style="text-align:center;padding:60px 20px;color:var(--s-text-3);">
    <i class="las la-check-circle" style="font-size:48px;color:#16a34a;margin-bottom:12px;display:block;"></i>
    <b style="font-size:16px;color:var(--s-text-1);">Todo en orden</b><br>
    <span style="font-size:13px;">No hay alertas de stock en este momento.</span>
</div>
@endif

</div>
@endsection
