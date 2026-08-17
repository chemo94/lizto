@extends('admin.layouts.app')
@section('panel')
<div class="row gy-4">
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--primary text-white">
            <div class="widget-card__icon"><i class="las la-receipt"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['today_orders'] }}</h2><p>Pedidos Hoy</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--warning text-white">
            <div class="widget-card__icon"><i class="las la-clock"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['pending_orders'] }}</h2><p>Pedidos Pendientes</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--success text-white">
            <div class="widget-card__icon"><i class="las la-check-circle"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['delivered_orders'] }}</h2><p>Pedidos Entregados</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--danger text-white">
            <div class="widget-card__icon"><i class="las la-times-circle"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['canceled_orders'] }}</h2><p>Pedidos Cancelados</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--info text-white">
            <div class="widget-card__icon"><i class="las la-hand-holding-heart"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['today_favors'] }}</h2><p>Favores Hoy</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--dark text-white">
            <div class="widget-card__icon"><i class="las la-store"></i></div>
            <div class="widget-card__content"><h2 class="text-white">{{ $stats['active_stores'] }}</h2><p>Tiendas Activas</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--cyan text-white">
            <div class="widget-card__icon"><i class="las la-dollar-sign"></i></div>
            <div class="widget-card__content"><h2 class="text-white">S/ {{ number_format($stats['today_revenue'], 2) }}</h2><p>Ingresos Hoy</p></div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="widget-card bg--orange text-white">
            <div class="widget-card__icon"><i class="las la-percent"></i></div>
            <div class="widget-card__content"><h2 class="text-white">S/ {{ number_format($stats['total_commission'], 2) }}</h2><p>Comisión Total</p></div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h5>Configuración de Comisión</h5>
        <p>Delivery: {{ $commission?->delivery_percent ?? 10 }}% | Favor: {{ $commission?->favor_percent ?? 15 }}% | Mínima: S/ {{ $commission?->min_commission ?? 1 }}</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Acciones rápidas</h5></div>
            <div class="card-body">
                <a href="{{ route('admin.delivery.orders') }}" class="btn btn--primary btn--sm mb-2 w-100">Ver Pedidos</a>
                <a href="{{ route('admin.delivery.favors') }}" class="btn btn--warning btn--sm mb-2 w-100">Ver Favores</a>
                <a href="{{ route('admin.delivery.stores') }}" class="btn btn--info btn--sm mb-2 w-100">Ver Tiendas</a>
                <a href="{{ route('admin.delivery.refunds') }}" class="btn btn--danger btn--sm mb-2 w-100">Ver Reembolsos</a>
                <a href="{{ route('admin.delivery.commission') }}" class="btn btn--dark btn--sm mb-2 w-100">Configurar Comisión</a>
                <a href="{{ route('admin.delivery.wallets') }}" class="btn btn--success btn--sm w-100">Gestionar Billeteras</a>
            </div>
        </div>
    </div>
</div>
@endsection
