@extends('admin.layouts.app')

@section('panel')
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="success" title="Saldo disponible"
                :value="$balance['available']" icon="las la-wallet" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="primary" title="Ganancia pendiente"
                :value="$widget['pending_earning']" icon="las la-hand-holding-usd" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="info" title="Histórico viajes"
                :value="$widget['ride']['earning']" icon="las la-car" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="warning" title="Histórico delivery"
                :value="$widget['delivery']['earning']" icon="las la-motorcycle" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="danger" title="Comisiones descontadas"
                :value="$widget['total_commission']" icon="las la-percentage" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="warning" title="Propinas de viajes"
                :value="$widget['total_tips']" icon="las la-gift" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="success" title="Generado hoy"
                :value="$widget['today_earning']" icon="las la-calendar-day" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-admin.ui.widget.four url="#" :currency="true" variant="primary" title="Generado este mes"
                :value="$widget['this_month_earning']" icon="las la-calendar-alt" />
        </div>
    </div>

    @if($balance['has_mismatch'])
        <div class="alert alert--warning mb-3">
            <div class="d-flex align-items-start gap-2">
                <i class="las la-exclamation-triangle fs-22"></i>
                <div>
                    <strong>Se detectó una diferencia entre saldos.</strong>
                    <div class="small mt-1">
                        Saldo usado por la app: {{ showAmount($balance['available']) }}
                        @if($balance['wallet'] !== null)
                            · Wallet universal: {{ showAmount($balance['wallet']) }}
                        @endif
                        @if($balance['transaction'] !== null)
                            · Último saldo registrado: {{ showAmount($balance['transaction']) }}
                        @endif
                    </div>
                    <div class="small">La pantalla utiliza como saldo disponible el mismo campo que consume la aplicación del conductor.</div>
                </div>
            </div>
        </div>
    @endif

    @if(strtolower((string) $driver->service_type) === 'delivery' || $driver->earning_balance > 0)
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <x-admin.ui.card class="h-100">
                    <x-admin.ui.card.header>
                        <h5 class="card-title mb-0"><i class="las la-piggy-bank"></i> Ganancia pendiente de depósito</h5>
                    </x-admin.ui.card.header>
                    <x-admin.ui.card.body>
                        <div class="text-center mb-3">
                            <small class="text-muted d-block">No se afecta cuando entrega efectivo en mano</small>
                            <strong class="text--primary" style="font-size:30px">{{ showAmount($driver->earning_balance) }}</strong>
                        </div>
                        @if($driver->earning_balance > 0)
                            <form method="POST" action="{{ route('admin.driver.earning.settle', $driver->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Monto a depositar</label>
                                    <input type="number" name="amount" class="form-control" min="0.01" max="{{ $driver->earning_balance }}" step="0.01" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Destino</label>
                                    <select name="settlement_method" class="form-select" required>
                                        <option value="balance">Saldo del repartidor</option>
                                        <option value="bank">Cuenta bancaria</option>
                                        <option value="yape">Yape</option>
                                        <option value="plin">Plin</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" name="reference" class="form-control" maxlength="100" placeholder="Operación, cuenta, Yape o Plin">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nota</label>
                                    <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                                </div>
                                <button class="btn btn--primary w-100" onclick="return confirm('¿Confirmas el depósito de esta ganancia?')">
                                    <i class="las la-check-circle"></i> Registrar depósito
                                </button>
                            </form>
                        @else
                            <div class="alert alert--success mb-0">No hay ganancia pendiente por depositar.</div>
                        @endif
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
            <div class="col-lg-8">
                <x-admin.ui.card class="h-100">
                    <x-admin.ui.card.header><h5 class="card-title mb-0">Movimientos de ganancia</h5></x-admin.ui.card.header>
                    <x-admin.ui.card.body :paddingZero="true">
                        <div class="table-responsive">
                            <table class="table table--light style--two mb-0">
                                <thead><tr><th>Tipo</th><th>Destino</th><th>Referencia</th><th>Monto</th><th>Saldo</th><th>Fecha</th></tr></thead>
                                <tbody>
                                    @forelse($earningTransactions as $tx)
                                        <tr>
                                            <td><span class="badge {{ $tx->trx_type === '+' ? 'badge--success' : 'badge--primary' }}">{{ $tx->type === 'earning' ? 'Ganancia' : 'Depósito' }}</span></td>
                                            <td>{{ $tx->settlement_method ? ucfirst($tx->settlement_method) : 'Pendiente' }}</td>
                                            <td>{{ $tx->reference ?: '—' }}</td>
                                            <td class="fw-bold {{ $tx->trx_type === '+' ? 'text--success' : 'text--primary' }}">{{ $tx->trx_type }} {{ showAmount($tx->amount) }}</td>
                                            <td>{{ showAmount($tx->post_balance) }}</td>
                                            <td>{{ showDateTime($tx->created_at) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center py-4">Sin movimientos de ganancia.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
        </div>
    @endif

    @if(strtolower((string) $driver->service_type) === 'delivery' || $driver->cash_in_hand > 0)
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <x-admin.ui.card class="h-100">
                    <x-admin.ui.card.header>
                        <h5 class="card-title mb-0"><i class="las la-money-bill-wave"></i> Efectivo en mano</h5>
                    </x-admin.ui.card.header>
                    <x-admin.ui.card.body>
                        <div class="text-center mb-3">
                            <small class="text-muted d-block">Pendiente de entregar a Lizto</small>
                            <strong class="text--danger" style="font-size:30px">{{ showAmount($driver->cash_in_hand) }}</strong>
                        </div>
                        @if($driver->cash_in_hand > 0)
                            <form method="POST" action="{{ route('admin.driver.earning.cash.remit', $driver->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Monto entregado</label>
                                    <input type="number" name="amount" class="form-control" min="0.01" max="{{ $driver->cash_in_hand }}" step="0.01" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" name="reference" class="form-control" maxlength="100" placeholder="Recibo, operación o constancia">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nota</label>
                                    <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                                </div>
                                <button class="btn btn--success w-100" onclick="return confirm('¿Confirmas que administración recibió este efectivo?')">
                                    <i class="las la-hand-holding-usd"></i> Registrar rendición
                                </button>
                            </form>
                        @else
                            <div class="alert alert--success mb-0">El repartidor no tiene efectivo pendiente.</div>
                        @endif
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
            <div class="col-lg-8">
                <x-admin.ui.card class="h-100">
                    <x-admin.ui.card.header><h5 class="card-title mb-0">Movimientos de efectivo</h5></x-admin.ui.card.header>
                    <x-admin.ui.card.body :paddingZero="true">
                        <div class="table-responsive">
                            <table class="table table--light style--two mb-0">
                                <thead><tr><th>Tipo</th><th>Referencia</th><th>Monto</th><th>Saldo</th><th>Registrado por</th><th>Fecha</th></tr></thead>
                                <tbody>
                                    @forelse($cashTransactions as $tx)
                                        <tr>
                                            <td><span class="badge {{ $tx->trx_type === '+' ? 'badge--warning' : 'badge--success' }}">{{ $tx->type === 'collection' ? 'Cobro' : 'Rendición' }}</span></td>
                                            <td>{{ $tx->reference ?: '—' }}</td>
                                            <td class="fw-bold {{ $tx->trx_type === '+' ? 'text--danger' : 'text--success' }}">{{ $tx->trx_type }} {{ showAmount($tx->amount) }}</td>
                                            <td>{{ showAmount($tx->post_balance) }}</td>
                                            <td>{{ $tx->admin?->username ?? 'Sistema' }}</td>
                                            <td>{{ showDateTime($tx->created_at) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center py-4">Sin movimientos de efectivo.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach(['ride' => ['Viajes', 'car', 'info'], 'delivery' => ['Delivery', 'motorcycle', 'warning']] as $type => $meta)
            @php($summary = $widget[$type])
            <div class="col-lg-6">
                <x-admin.ui.card class="h-100">
                    <x-admin.ui.card.header>
                        <h5 class="card-title mb-0"><i class="las la-{{ $meta[1] }}"></i> {{ $meta[0] }}</h5>
                    </x-admin.ui.card.header>
                    <x-admin.ui.card.body>
                        <div class="earning-summary-grid">
                            <div><span>Servicios</span><strong>{{ $summary['count'] }}</strong></div>
                            <div><span>Ingreso bruto</span><strong>{{ showAmount($summary['gross']) }}</strong></div>
                            <div><span>Comisión</span><strong class="text--danger">- {{ showAmount($summary['commission']) }}</strong></div>
                            <div><span>Ganancia generada</span><strong class="text--success">{{ showAmount($summary['earning']) }}</strong></div>
                        </div>
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
        @endforeach
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <x-admin.ui.card class="shadow-none dw-card">
                <x-admin.ui.card.header class="flex-between py-3 gap-2">
                    <h5 class="card-title mb-0 fs-16">Ganancia por tipo de servicio</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select form-select-sm chart-period">
                            <option value="daily">Últimos 7 días</option>
                            <option value="date_range">Rango de fechas</option>
                        </select>
                        <div class="date-picker-wrapper d-none">
                            <input type="text" class="form-control form-control-sm date-picker" name="date" placeholder="Seleccionar fechas">
                        </div>
                    </div>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body>
                    <div id="dwChartArea"></div>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.header class="flex-between gap-2 flex-wrap">
                    <div>
                        <h4 class="card-title mb-1">Detalle real de ganancias</h4>
                        <small class="text-muted">La comisión se descuenta del saldo de recarga, no de la ganancia.</small>
                    </div>
                    <form method="GET" class="d-flex gap-2">
                        <select name="service_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" @selected($serviceType === 'all')>Todos los servicios</option>
                            <option value="ride" @selected($serviceType === 'ride')>Sólo viajes</option>
                            <option value="delivery" @selected($serviceType === 'delivery')>Sólo delivery</option>
                        </select>
                    </form>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body :paddingZero="true">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">Propina</th>
                                    <th class="text-end">Comisión</th>
                                    <th class="text-end">Ganancia</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($earnings as $earning)
                                    <tr>
                                        <td>
                                            @if($earning->service_type === 'ride')
                                                <span class="badge badge--info"><i class="las la-car"></i> Viaje</span>
                                            @else
                                                <span class="badge badge--warning"><i class="las la-motorcycle"></i> Delivery</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($earning->service_type === 'ride')
                                                <a href="{{ route('admin.rides.detail', $earning->source_id) }}">{{ $earning->reference }}</a>
                                            @elseif($earning->job_type === \App\Models\DeliveryOrder::class)
                                                <a href="{{ route('admin.delivery.order.detail', $earning->source_id) }}">{{ $earning->reference }}</a>
                                            @elseif($earning->job_type === \App\Models\Favor::class)
                                                <a href="{{ route('admin.delivery.favor.detail', $earning->source_id) }}">{{ $earning->reference }}</a>
                                            @else
                                                {{ $earning->reference }}
                                            @endif
                                        </td>
                                        <td>{{ $earning->description }}</td>
                                        <td class="text-end">{{ showAmount($earning->gross_amount) }}</td>
                                        <td class="text-end">{{ showAmount($earning->tips_amount) }}</td>
                                        <td class="text-end text--danger">- {{ showAmount($earning->commission_amount) }}</td>
                                        <td class="text-end fw-bold text--success">{{ showAmount($earning->earning_amount) }}</td>
                                        <td>
                                            <strong class="d-block">{{ showDateTime($earning->earned_at) }}</strong>
                                            <small>{{ diffForHumans($earning->earned_at) }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-5">No hay ganancias para el tipo de servicio seleccionado.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-admin.ui.card.body>
                @if($earnings->hasPages())
                    <div class="card-footer">
                        {{ paginateLinks($earnings) }}
                    </div>
                @endif
            </x-admin.ui.card>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/flatpickr.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
    <script src="{{ asset('assets/global/js/flatpickr.js') }}"></script>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            const chart = barChart(
                document.querySelector('#dwChartArea'),
                @json(__(gs('cur_text'))),
                [
                    { name: 'Viajes', data: [] },
                    { name: 'Delivery', data: [] },
                    { name: 'Total', data: [] }
                ],
                []
            );

            function loadChart() {
                const period = $('.chart-period').val();
                $('.date-picker-wrapper').toggleClass('d-none', period !== 'date_range');

                $.get(@json(route('admin.driver.earning.chart', $driver->id)), {
                    time_period: period,
                    date: $('input[name=date]').val(),
                    service_type: @json($serviceType)
                }, function(data) {
                    chart.updateSeries([
                        { name: 'Viajes', data: Object.values(data).map(item => item.ride_amount) },
                        { name: 'Delivery', data: Object.values(data).map(item => item.delivery_amount) },
                        { name: 'Total', data: Object.values(data).map(item => item.total_amount) }
                    ]);
                    chart.updateOptions({ xaxis: { categories: Object.keys(data) } });
                });
            }

            $('.chart-period').on('change', loadChart);
            $('.date-picker').flatpickr({ mode: 'range', maxDate: new Date(), onClose: loadChart });
            loadChart();
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .earning-summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .earning-summary-grid div { display:flex; flex-direction:column; gap:4px; }
        .earning-summary-grid span { color:var(--body-color); font-size:12px; }
        .earning-summary-grid strong { font-size:18px; }
        @media(max-width:767px) { .earning-summary-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
@endpush
