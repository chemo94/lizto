@extends('seller.layouts.app')
@section('panel')
<div style="padding: 24px; max-width: 1200px; margin: 0 auto;">
    
    <!-- HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-weight: 800; font-size: 24px; color: var(--s-text-primary); margin: 0;">CRM de Clientes</h2>
            <p style="color: var(--s-text-muted); margin: 4px 0 0 0; font-size: 14px;">Administra la relación con tus clientes y visualiza su historial de compras.</p>
        </div>
    </div>

    <!-- METRICS CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="s-card" style="display: flex; align-items: center; gap: 16px; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="background: rgba(var(--s-primary-rgb), 0.1); color: var(--s-primary); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class="las la-users"></i>
            </div>
            <div>
                <span style="font-size: 13px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 700;">Total Clientes</span>
                <h3 style="margin: 4px 0 0 0; font-weight: 800; font-size: 20px; color: var(--s-text-primary);">{{ $customers->total() }}</h3>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="s-card" style="padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <form method="GET" action="{{ route('seller.crm.customers') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 250px; position: relative;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o número de documento..." style="width: 100%; border-radius: 10px; height: 44px; padding: 0 16px 0 40px; border: 1px solid var(--s-border); font-size: 14px;">
                <i class="las la-search" style="position: absolute; left: 14px; top: 14px; font-size: 18px; color: var(--s-text-muted);"></i>
            </div>
            <button type="submit" class="s-btn s-btn-primary" style="height: 44px; border-radius: 10px; padding: 0 20px; font-weight: 700;">
                Buscar
            </button>
            @if(request('search'))
                <a href="{{ route('seller.crm.customers') }}" class="s-btn s-btn-ghost" style="height: 44px; border-radius: 10px; padding: 0 16px; display: inline-flex; align-items: center; border: 1px solid var(--s-border);">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    <!-- CLIENTS TABLE -->
    <div class="s-card" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 0; overflow: hidden;">
        <div class="s-table-wrapper" style="margin: 0;">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th style="text-align: center;">Nº Compras</th>
                        <th style="text-align: right;">Total Comprado</th>
                        <th>Última Compra</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    <tr>
                        <td style="font-weight: 700; color: var(--s-text-primary);">
                            {{ $c->customer_name ?: 'Cliente Sin Nombre' }}
                        </td>
                        <td>
                            @if($c->customer_doc)
                                <span class="s-badge s-badge-gray" style="font-size: 10px; margin-right: 4px;">
                                    {{ $c->customer_doc_type == '6' ? 'RUC' : ($c->customer_doc_type == '1' ? 'DNI' : 'OTRO') }}
                                </span>
                                <span style="font-weight: 600;">{{ $c->customer_doc }}</span>
                            @else
                                <span class="s-badge s-badge-gray" style="font-size: 10px; margin-right: 4px;">
                                    TEL
                                </span>
                                <span style="font-weight: 600;">{{ $c->customer_phone }}</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 700; color: var(--s-primary);">
                            {{ $c->total_orders }}
                        </td>
                        <td style="text-align: right; font-weight: 800; color: var(--s-text-primary);">
                            S/ {{ number_format($c->total_spent, 2) }}
                        </td>
                        <td>
                            @if($c->last_purchase_at)
                                <span style="font-size: 13px;">{{ \Carbon\Carbon::parse($c->last_purchase_at)->format('d/m/Y H:i') }}</span>
                            @else
                                <span style="color: var(--s-text-muted); font-size: 12px;">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('seller.crm.customer.profile', $c->customer_key) }}" class="s-btn s-btn-primary s-btn-xs" style="padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; font-size: 12px;">
                                <i class="las la-user"></i> Ver Perfil
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--s-text-muted);">
                            <i class="las la-users" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.5;"></i>
                            No se encontraron clientes registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- PAGINATION -->
        @if ($customers->hasPages())
        <div style="padding: 20px; display: flex; justify-content: center; border-top: 1px solid var(--s-border);">
            {{ $customers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
