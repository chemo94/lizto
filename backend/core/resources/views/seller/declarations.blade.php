@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-file-signature"></i></span> Declaraciones SUNAT / SIRE
@endsection

@section('seller-content')
<div class="s-content" style="max-width:1100px;margin:0 auto;">
    <section class="module-hero tax"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; SUNAT &nbsp;/&nbsp; Declaraciones</div><h2><i class="las la-file-signature"></i> Declaraciones SUNAT / SIRE</h2><p>Prepara registros electrónicos para revisión y presentación contable.</p></div><div class="module-hero-stats"><div><b>{{ $salesCount }}</b><small>Comprobantes</small></div><div><b>S/ {{ number_format($salesTotal,0) }}</b><small>Ventas</small></div><div><b>S/ {{ number_format($purchasesTotal,0) }}</b><small>Compras</small></div></div></section>
    <div class="s-card seller-work-card" style="margin-bottom:14px;">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label class="s-label">Mes de declaración</label>
                <input type="month" name="month" value="{{ $selectedMonth }}" class="s-input" required>
            </div>
            <button class="s-btn s-btn-primary"><i class="las la-filter"></i> Consultar mes</button>
        </form>
    </div>

    <div class="s-card" style="margin-bottom:14px;background:var(--s-info-bg);border-color:var(--s-info);">
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <i class="las la-info-circle" style="font-size:22px;color:var(--s-info);"></i>
            <div><b>Archivos para tu proceso contable.</b><br><span style="font-size:12px;color:var(--s-text-3);">Excel (.xlsx) se entrega para revisión, conciliación y trabajo contable. Para reemplazar una propuesta en SIRE, descarga el TXT y valídalo antes de enviarlo a SUNAT.</span></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;">
        <div class="s-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
                <div><h3 style="margin:0;font-size:18px;"><i class="las la-file-invoice" style="color:var(--s-info);"></i> RVIE</h3><p style="margin:5px 0 0;color:var(--s-text-3);font-size:12px;">Registro de Ventas e Ingresos Electrónico</p></div>
                <span class="s-badge s-badge-blue">{{ $salesCount }} comprobantes</span>
            </div>
            <div style="font-size:24px;font-weight:800;color:var(--s-accent-dark);margin-bottom:18px;">S/ {{ number_format($salesTotal, 2) }}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <a class="s-btn s-btn-outline" href="{{ route('seller.reports.export.rvie', ['month'=>$selectedMonth,'format'=>'excel']) }}"><i class="las la-file-excel" style="color:#16a34a;"></i> XLSX</a>
                <a class="s-btn s-btn-primary" href="{{ route('seller.reports.export.rvie', ['month'=>$selectedMonth,'format'=>'txt']) }}"><i class="las la-file-alt"></i> TXT SIRE</a>
            </div>
        </div>
        <div class="s-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
                <div><h3 style="margin:0;font-size:18px;"><i class="las la-shopping-cart" style="color:var(--s-warning);"></i> RCE</h3><p style="margin:5px 0 0;color:var(--s-text-3);font-size:12px;">Registro de Compras Electrónico</p></div>
                <span class="s-badge s-badge-yellow">{{ $purchasesCount }} compras</span>
            </div>
            <div style="font-size:24px;font-weight:800;color:var(--s-accent-dark);margin-bottom:18px;">S/ {{ number_format($purchasesTotal, 2) }}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <a class="s-btn s-btn-outline" href="{{ route('seller.reports.export.rce', ['month'=>$selectedMonth,'format'=>'excel']) }}"><i class="las la-file-excel" style="color:#16a34a;"></i> XLSX</a>
                <a class="s-btn s-btn-primary" href="{{ route('seller.reports.export.rce', ['month'=>$selectedMonth,'format'=>'txt']) }}"><i class="las la-file-alt"></i> TXT SIRE</a>
            </div>
        </div>
    </div>
</div>
@endsection
