@extends('admin.layouts.app')
@section('panel')
    <p class="lz-subtitle">Resumen de tu plataforma · Viajes, personas y finanzas en un solo lugar.</p>
    <x-permission_check permission="view rider payment report">
        <x-admin.ui.widget.group.dashboard.payment :widget="$widget" />
    </x-permission_check>

    <x-permission_check permission="view rides">
        <x-admin.ui.widget.group.dashboard.ride :widget="$widget" />
        @php
            $completedPercent = $widget['total_ride'] ? round($widget['completed_ride'] / $widget['total_ride'] * 100, 1) : 0;
            $cancelledPercent = $widget['total_ride'] ? round($widget['canceled_ride'] / $widget['total_ride'] * 100, 1) : 0;
        @endphp
        <div class="lz-columns">
            <section class="card"><div class="card-body"><h5 class="card-title">Resumen de viajes</h5><p class="text-muted">Distribución histórica de la operación</p>
                <div class="lz-progress" aria-label="Distribución de viajes"><span style="width:{{ $completedPercent }}%;background:#71dd37"></span><span style="width:{{ $cancelledPercent }}%;background:#ffab00"></span><span style="flex:1;background:#696cff"></span></div>
                <ul class="lz-list"><li><span>Completados</span><strong>{{ $widget['completed_ride'] }} <small class="text-muted">· {{ $completedPercent }}%</small></strong></li><li><span>Cancelados</span><strong>{{ $widget['canceled_ride'] }} <small class="text-muted">· {{ $cancelledPercent }}%</small></strong></li><li><span>En curso</span><strong>{{ $widget['running_ride'] }}</strong></li><li><span>Total de viajes</span><strong>{{ $widget['total_ride'] }}</strong></li></ul>
            </div></section>
            <section class="card"><div class="card-body"><h5 class="card-title">Tasa de viajes completados</h5><p class="text-muted">Sobre el total de viajes registrados</p><div class="lz-donut" style="--percentage:{{ $completedPercent }}%"><div><strong>{{ $completedPercent }}%</strong><span>Completados</span></div></div><p class="text-muted text-center small mb-0">{{ $widget['completed_ride'] }} de {{ $widget['total_ride'] }} viajes</p></div></section>
        </div>
    </x-permission_check>

    <x-permission_check permission="view riders">
        <x-admin.ui.widget.group.dashboard.users :widget="$widget" />
    </x-permission_check>

    <x-permission_check permission="view drivers">
        <x-admin.ui.widget.group.dashboard.driver :widget="$widget" />
    </x-permission_check>

    <x-permission_check permission="view driver deposits">
        <x-admin.ui.widget.group.dashboard.financial_overview :widget="$widget" />
    </x-permission_check>

    <div class="row gy-4 mb-4">
        <x-permission_check permission="view driver transaction history">
            <x-admin.other.dashboard_trx_chart />
        </x-permission_check>
        <div class="col-xl-4">
            <x-permission_check permission="view rider login history">
                <x-admin.other.dashboard_login_chart :userLogin=$userLogin />
            </x-permission_check>
        </div>
    </div>
    <x-permission_check permission="cron job settings">
        <x-admin.other.cron_modal />
    </x-permission_check>
    <x-permission_check permission="view rides">
        <section class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Viajes recientes</h5><a class="btn btn-outline--primary btn-sm" href="{{ route('admin.rides.all') }}">Ver todos</a></div><div class="table-responsive"><table class="table"><thead><tr><th>Viaje</th><th>Conductor</th><th>Origen</th><th>Destino</th><th>Estado</th><th>Importe</th></tr></thead><tbody>
            @forelse($recentRides as $ride)<tr><td><a href="{{ route('admin.rides.detail',$ride->id) }}">#{{ $ride->id }}</a></td><td>{{ $ride->driver?->fullname ?? 'Sin asignar' }}</td><td>{{ $ride->pickup_location }}</td><td>{{ $ride->destination }}</td><td>{!! $ride->statusBadge !!}</td><td>{{ showAmount($ride->amount) }}</td></tr>@empty<tr><td colspan="6" class="text-center py-5 text-muted">Los nuevos viajes aparecerán aquí.</td></tr>@endforelse
        </tbody></table></div></section>
    </x-permission_check>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
    <script src="{{ asset('assets/global/js/flatpickr.js') }}"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/{{ config('app.locale') }}.js"></script>
@endpush


@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/flatpickr.min.css') }}">
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            $(".date-picker").flatpickr({
                mode: 'range',
                maxDate: new Date(),
                locale: "{{ config('app.locale') }}"
            });
        })(jQuery);
    </script>
@endpush
