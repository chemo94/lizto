@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12 mb-3">
            <a href="{{ route('admin.seller.index') }}" class="btn btn-sm btn-outline-primary"><i class="la la-arrow-left"></i> @lang('Back')</a>
        </div>
        <div class="col-md-4">
            <x-admin.ui.card>
                <x-admin.ui.card.body>
                    <div class="text-center mb-3">
                        <img src="{{ getImage(getFilePath('user') . '/' . $seller->avatar, getFilePath('user'), isAvatar: true) }}" class="rounded-circle" width="100">
                        <h5 class="mt-2">{{ __($seller->name) }}</h5>
                        <p>{{ $seller->email }}</p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>@lang('Phone')</span>
                            <span>{{ $seller->phone ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>@lang('Document')</span>
                            <span>{{ $seller->document_type ?? 'N/A' }}: {{ $seller->document_number ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>@lang('Verified')</span>
                            <span>{!! $seller->is_verified ? '<span class="badge badge--success">Yes</span>' : '<span class="badge badge--warning">No</span>' !!}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>@lang('Status')</span>
                            <span>{!! $seller->status ? '<span class="badge badge--success">Active</span>' : '<span class="badge badge--danger">Banned</span>' !!}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>@lang('Joined')</span>
                            <span>{{ showDateTime($seller->created_at) }}</span>
                        </li>
                    </ul>
                    <div class="d-flex gap-2 mt-3">
                        <x-admin.other.status_switch :status="$seller->status" :action="route('admin.seller.status', $seller->id)" title="seller" />
                        @if(!$seller->is_verified)
                            <a href="{{ route('admin.seller.verification', $seller->id) }}" class="btn btn-sm btn-outline-success">
                                <i class="la la-check"></i> @lang('Verify')
                            </a>
                        @endif
                    </div>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
        <div class="col-md-8">
            <x-admin.ui.card>
                <x-admin.ui.card.header>
                    <h5>@lang('Stores') ({{ $seller->stores->count() }})</h5>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table>
                        <x-admin.ui.table.header>
                            <tr>
                                <th>@lang('Store')</th>
                                <th>@lang('Category')</th>
                                <th>@lang('Products')</th>
                                <th>@lang('Status')</th>
                            </tr>
                        </x-admin.ui.table.header>
                        <x-admin.ui.table.body>
                            @forelse($seller->stores as $store)
                                <tr>
                                    <td>
                                        <div class="flex-thumb-wrapper gap-1">
                                            <div class="thumb">
                                                <img src="{{ getImage(getFilePath('store') . '/' . $store->image, getFilePath('store')) }}" width="40">
                                            </div>
                                            <span>{{ __($store->name) }}</span>
                                        </div>
                                    </td>
                                    <td>{{ __($store->subCategory?->name ?? 'N/A') }}</td>
                                    <td>{{ $store->products->count() }}</td>
                                    <td>
                                        @if($store->status)
                                            <span class="badge badge--success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge--danger">@lang('Inactive')</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-admin.ui.table.empty_message />
                            @endforelse
                        </x-admin.ui.table.body>
                    </x-admin.ui.table>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-4">
            <x-admin.ui.card class="h-100">
                <x-admin.ui.card.header><h5><i class="las la-file-invoice-dollar"></i> Cuenta por cobrar a Lizto</h5></x-admin.ui.card.header>
                <x-admin.ui.card.body>
                    <div class="text-center mb-3">
                        <small class="text-muted d-block">Saldo pendiente de liquidación</small>
                        <strong class="text--success" style="font-size:30px">{{ showAmount($seller->receivable_balance) }}</strong>
                    </div>
                    @if($seller->receivable_balance > 0)
                        <form method="POST" action="{{ route('admin.seller.receivable.settle', $seller->id) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Monto a liquidar</label>
                                <input type="number" name="amount" class="form-control" min="0.01" max="{{ $seller->receivable_balance }}" step="0.01" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Destino</label>
                                <select name="settlement_method" class="form-select" required>
                                    <option value="balance">Saldo del seller</option>
                                    <option value="bank">Cuenta bancaria</option>
                                    <option value="yape">Yape</option>
                                    <option value="plin">Plin</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Referencia de operación</label>
                                <input type="text" name="reference" class="form-control" maxlength="150" placeholder="Obligatoria para transferencias externas">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nota</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                            </div>
                            <button class="btn btn--success w-100" onclick="return confirm('¿Confirmas que esta liquidación fue realizada?')">
                                <i class="las la-money-check-alt"></i> Registrar liquidación
                            </button>
                        </form>
                    @else
                        <div class="alert alert--success mb-0">No existen montos pendientes de liquidación.</div>
                    @endif
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
        <div class="col-lg-8">
            <x-admin.ui.card class="h-100">
                <x-admin.ui.card.header><h5>Movimientos de cuenta por cobrar</h5></x-admin.ui.card.header>
                <x-admin.ui.card.body :paddingZero="true">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead><tr><th>Tipo</th><th>Canal / destino</th><th>Referencia</th><th>Monto</th><th>Saldo</th><th>Fecha</th></tr></thead>
                            <tbody>
                                @forelse($receivableTransactions as $tx)
                                    <tr>
                                        <td><span class="badge {{ $tx->trx_type === '+' ? 'badge--success' : 'badge--info' }}">{{ $tx->type === 'earning' ? 'Por cobrar' : 'Liquidación' }}</span></td>
                                        <td>{{ ucfirst($tx->payment_channel ?: $tx->settlement_method ?: '—') }}</td>
                                        <td>{{ $tx->reference ?: '—' }}</td>
                                        <td class="fw-bold {{ $tx->trx_type === '+' ? 'text--success' : 'text--danger' }}">{{ $tx->trx_type }} {{ showAmount($tx->amount) }}</td>
                                        <td>{{ showAmount($tx->post_balance) }}</td>
                                        <td>{{ showDateTime($tx->created_at) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4">Sin movimientos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>

    {{-- QR Management for each store --}}
    @foreach($seller->stores as $store)
    <div class="col-12 mt-3">
        <x-admin.ui.card>
            <x-admin.ui.card.header>
                <h5><i class="la la-qrcode"></i> @lang('QR Pagos') — {{ $store->name }}</h5>
            </x-admin.ui.card.header>
            <x-admin.ui.card.body>
                <form method="POST" action="{{ route('admin.seller.store.qr.save', $store->id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="display:flex;align-items:center;gap:6px">
                                <span class="badge bg-purple" style="background:#7c3aed">YAPE</span> Cadena QR de Yape
                            </label>
                            <textarea name="yape_qr_string" class="form-control" rows="3"
                                placeholder="Pega la cadena de texto del QR de Yape (empieza con 00020101...)">{{ $store->yape_qr_string }}</textarea>
                            <small class="text-muted">Abre Yape → Cobrar → Compartir QR → copia la cadena.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="display:flex;align-items:center;gap:6px">
                                <span class="badge" style="background:#0ea5e9">PLIN</span> Cadena QR de Plin
                            </label>
                            <textarea name="plin_qr_string" class="form-control" rows="3"
                                placeholder="Pega la cadena de texto del QR de Plin">{{ $store->plin_qr_string }}</textarea>
                            <small class="text-muted">Abre Plin → Cobrar → QR estático → copia la cadena.</small>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="la la-save"></i> @lang('Save QR Settings')
                            </button>
                        </div>
                    </div>
                </form>
            </x-admin.ui.card.body>
        </x-admin.ui.card>
    </div>
    @endforeach
</div>
@endsection
