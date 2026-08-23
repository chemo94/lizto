@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-cash-register"></i></span> Caja
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero cash"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Finanzas &nbsp;/&nbsp; Caja</div><h2><i class="las la-cash-register"></i> Control de Caja</h2><p>Supervisa turnos, movimientos, ventas y arqueos del negocio.</p></div><div class="module-hero-stats"><div><b>{{ $registers->count() }}</b><small>Cajas</small></div><div><b>{{ $registers->filter(fn($r) => $r->openSession)->count() }}</b><small>Abiertas</small></div><div><b>{{ $openSession ? 'Activa' : 'Cerrada' }}</b><small>Caja seleccionada</small></div></div></section>

    <div style="display:flex;gap:8px;margin-bottom:14px;border-bottom:1px solid var(--s-border);padding-bottom:10px;overflow-x:auto">
        <a href="{{ route('seller.cash') }}" style="text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;color:{{ request()->routeIs('seller.cash') ? '#fff' : 'var(--s-text-2)' }};background:{{ request()->routeIs('seller.cash') ? 'var(--s-primary)' : 'transparent' }};border:{{ request()->routeIs('seller.cash') ? 'none' : '1px solid var(--s-border)' }}">
            <i class="las la-cash-register" style="font-size:16px"></i> Caja Chica / POS
        </a>
        <a href="{{ route('seller.registers') }}" style="text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;color:{{ request()->routeIs('seller.registers') ? '#fff' : 'var(--s-text-2)' }};background:{{ request()->routeIs('seller.registers') ? 'var(--s-primary)' : 'transparent' }};border:{{ request()->routeIs('seller.registers') ? 'none' : '1px solid var(--s-border)' }}">
            <i class="las la-cog" style="font-size:16px"></i> Configuración de Cajas
        </a>
        <a href="{{ route('seller.pos.bank_accounts') }}" style="text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;color:{{ request()->routeIs('seller.pos.bank_accounts') ? '#fff' : 'var(--s-text-2)' }};background:{{ request()->routeIs('seller.pos.bank_accounts') ? 'var(--s-primary)' : 'transparent' }};border:{{ request()->routeIs('seller.pos.bank_accounts') ? 'none' : '1px solid var(--s-border)' }}">
            <i class="las la-university" style="font-size:16px"></i> Cuentas Bancarias / Monederos
        </a>
    </div>

    <!-- CAJA SELECTOR AND NAME -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;background:#f8fafc;padding:16px 20px;border-radius:12px;border:1px solid var(--s-border)">
        <div>
            <h2 style="font-size:18px;font-weight:800;margin:0;color:var(--s-text);display:flex;align-items:center;gap:8px">
                <i class="las la-cash-register" style="color:var(--s-primary);font-size:24px"></i> {{ strtoupper($selectedRegister->name) }}
            </h2>
            @if($selectedRegister->description)
                <p style="color:var(--s-text-3);font-size:12px;margin:4px 0 0">{{ $selectedRegister->description }}</p>
            @endif
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <span style="font-size:12px;font-weight:600;color:var(--s-text-2)">Seleccionar Caja:</span>
            <select class="s-input" onchange="location.href='{{ route('seller.cash') }}?register=' + this.value" style="width:auto;height:38px;padding:4px 30px 4px 12px;font-size:13px;border-radius:8px">
                @foreach($registers as $reg)
                    @if($reg->is_active || $reg->openSession || $reg->id == $selectedRegister->id)
                        <option value="{{ $reg->id }}" {{ $reg->id == $selectedRegister->id ? 'selected' : '' }}>
                            {{ $reg->name }} {{ $reg->openSession ? '(Abierta)' : (!$reg->is_active ? '(Inactiva)' : '(Cerrada)') }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #16a34a;color:#15803d;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;border:1px solid #dc2626;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px">{{ session('error') }}</div>
    @endif

    @if(!$openSession)
    <!-- ABRIR CAJA -->
    <div style="max-width:500px;margin:0 auto">
        <div class="s-card">
            <div style="text-align:center;padding:16px 0 24px">
                <div style="width:72px;height:72px;border-radius:20px;background:var(--s-accent-light);display:grid;place-items:center;margin:0 auto 16px;font-size:36px">💰</div>
                <h2 style="font-size:22px;font-weight:800;margin:0 0 6px;color:var(--s-text)">Abrir {{ $selectedRegister->name }}</h2>
                <p style="color:var(--s-text-3);font-size:13px;margin:0 0 28px">Ingresa el saldo inicial para comenzar el turno en esta caja</p>
            </div>
            <form method="POST" action="{{ route('seller.cash.open') }}">
                @csrf
                <input type="hidden" name="register_id" value="{{ $selectedRegister->id }}">
                <div class="s-form-grid" style="max-width:360px;margin:0 auto">
                    <div class="s-input-group">
                        <label class="s-input-label">Saldo Inicial (S/)</label>
                        <input class="s-input" type="number" name="opening_balance" step="0.01" value="0" required style="font-size:20px;font-weight:800;text-align:center">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Notas (opcional)</label>
                        <input class="s-input" name="notes" placeholder="Ej: Turno mañana">
                    </div>
                    <button class="s-btn s-btn-primary s-btn-lg" style="justify-content:center">
                        <i class="las la-door-open"></i> Abrir Caja
                    </button>
                </div>
            </form>
        </div>
    </div>

    @else
    <!-- CAJA ABIERTA -->
    @php
        $saldo = $openSession->opening_balance + $openSession->total_sales + $openSession->total_cash_in - $openSession->total_expenses - $openSession->total_cash_out;
    @endphp

    <!-- KPIs -->
    <div class="s-grid-4" style="margin-bottom:24px">
        <div class="s-stat">
            <div class="s-stat-icon blue"><i class="las la-door-open"></i></div>
            <div>
                <strong>S/ {{ number_format($openSession->opening_balance,2) }}</strong>
                <small>Apertura</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon green"><i class="las la-shopping-cart"></i></div>
            <div>
                <strong>S/ {{ number_format($openSession->total_sales,2) }}</strong>
                <small>Ventas</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon green"><i class="las la-arrow-down"></i></div>
            <div>
                <strong>S/ {{ number_format($openSession->total_cash_in,2) }}</strong>
                <small>Ingresos</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon red"><i class="las la-arrow-up"></i></div>
            <div>
                <strong>S/ {{ number_format($openSession->total_expenses,2) }}</strong>
                <small>Gastos</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon amber"><i class="las la-minus-circle"></i></div>
            <div>
                <strong>S/ {{ number_format($openSession->total_cash_out,2) }}</strong>
                <small>Egresos</small>
            </div>
        </div>
        <div class="s-stat" style="border-color:{{ $saldo >= 0 ? 'var(--s-accent)' : 'var(--s-danger)' }};background:{{ $saldo >= 0 ? 'var(--s-success-bg)' : 'var(--s-danger-bg)' }}">
            <div class="s-stat-icon {{ $saldo >= 0 ? 'green' : 'red' }}"><i class="las la-calculator"></i></div>
            <div>
                <strong style="color:{{ $saldo >= 0 ? 'var(--s-accent-dark)' : 'var(--s-danger)' }}">S/ {{ number_format($saldo,2) }}</strong>
                <small>Saldo Actual</small>
            </div>
        </div>
    </div>

    <!-- MOVIMIENTO + CERRAR -->
    <div class="s-grid-2" style="margin-bottom:24px">
        <!-- Movimientos -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-exchange-alt"></i> Registrar Movimiento</h3>
            <form method="POST" action="{{ route('seller.cash.transaction') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid var(--s-border)">
                @csrf
                <input type="hidden" name="register_id" value="{{ $selectedRegister->id }}">
                <div class="s-input-group" style="flex:0 0 auto">
                    <label class="s-input-label">Tipo</label>
                    <select class="s-input" name="type" style="width:110px">
                        <option value="cash_in">↑ Ingreso</option>
                        <option value="cash_out">↓ Egreso</option>
                    </select>
                </div>
                <div class="s-input-group" style="flex:0 0 auto">
                    <label class="s-input-label">Monto (S/)</label>
                    <input class="s-input" type="number" name="amount" step="0.01" placeholder="0.00" style="width:110px" required>
                </div>
                <div class="s-input-group" style="flex:1;min-width:140px">
                    <label class="s-input-label">Descripción</label>
                    <input class="s-input" name="description" placeholder="Descripción del movimiento" required>
                </div>
                <button class="s-btn s-btn-primary s-btn-sm" style="margin-bottom:1px">
                    <i class="las la-plus"></i> Registrar
                </button>
            </form>
            <div style="overflow-x:auto">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Descripción</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td>
                            <span class="s-badge {{ $t->type==='sale'?'s-badge-green':($t->type==='expense'?'s-badge-red':'s-badge-blue') }}">
                                {{ $t->type }}
                            </span>
                        </td>
                        <td><b>S/ {{ number_format($t->amount,2) }}</b></td>
                        <td style="color:var(--s-text-2)">{{ $t->description }}</td>
                        <td style="font-size:11px;color:var(--s-text-3)">{{ $t->created_at->format('H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="s-empty" style="padding:28px"><i class="las la-inbox"></i><p>Sin movimientos</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- Cerrar Caja con Arqueo Detallado -->
        <div>
            <div class="s-card" style="border-color:var(--s-danger-bg)">
                <h3 class="s-card-title"><i class="las la-door-closed"></i> Arqueo y Cierre — {{ $selectedRegister->name }}</h3>
                <div style="background:var(--s-warning-bg);border:1px solid var(--s-warning);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:var(--s-warning-text);display:flex;gap:8px">
                    <i class="las la-exclamation-triangle" style="font-size:16px;flex-shrink:0;margin-top:1px"></i>
                    <span>Realiza el conteo físico de billetes y monedas. El sistema calculará el total automáticamente.</span>
                </div>
                <form method="POST" action="{{ route('seller.cash.close') }}" id="arqueo-form">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $openSession->id }}">

                    <!-- BILLETES -->
                    <div style="margin-bottom:16px;">
                        <label style="font-weight:800;font-size:13px;display:block;margin-bottom:10px;color:var(--s-text);">
                            <i class="las la-money-bill-wave" style="color:var(--s-primary);"></i> Billetes
                        </label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:8px;">
                            @foreach([200,100,50,20,10] as $denom)
                            <div style="display:flex;align-items:center;gap:6px;background:var(--s-bg-light);border:1px solid var(--s-border);border-radius:10px;padding:8px 10px;">
                                <span style="font-weight:800;font-size:14px;color:var(--s-text);min-width:50px;">S/ {{ $denom }}</span>
                                <span style="font-size:10px;color:var(--s-text-3);">x</span>
                                <input type="number" name="b_{{ $denom }}" value="0" min="0" class="arqueo-input" data-denom="{{ $denom }}" oninput="calcArqueo()" style="width:60px;height:32px;text-align:center;font-weight:700;border-radius:6px;border:1px solid var(--s-border);font-size:13px;padding:2px 4px;">
                                <span style="font-size:10px;color:var(--s-text-2);min-width:40px;text-align:right;font-weight:600;" id="sub-b_{{ $denom }}">0.00</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- MONEDAS -->
                    <div style="margin-bottom:16px;">
                        <label style="font-weight:800;font-size:13px;display:block;margin-bottom:10px;color:var(--s-text);">
                            <i class="las la-coins" style="color:#f59e0b;"></i> Monedas
                        </label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:8px;">
                            @foreach([5,2,1,0.5,0.2,0.1] as $denom)
                            <div style="display:flex;align-items:center;gap:6px;background:var(--s-bg-light);border:1px solid var(--s-border);border-radius:10px;padding:8px 10px;">
                                <span style="font-weight:800;font-size:14px;color:var(--s-text);min-width:50px;">S/ {{ number_format($denom, $denom < 1 ? 2 : 0) }}</span>
                                <span style="font-size:10px;color:var(--s-text-3);">x</span>
                                <input type="number" name="b_{{ str_replace('.','_', (string)$denom) }}" value="0" min="0" class="arqueo-input" data-denom="{{ $denom }}" oninput="calcArqueo()" style="width:60px;height:32px;text-align:center;font-weight:700;border-radius:6px;border:1px solid var(--s-border);font-size:13px;padding:2px 4px;">
                                <span style="font-size:10px;color:var(--s-text-2);min-width:40px;text-align:right;font-weight:600;" id="sub-b_{{ str_replace('.','_', (string)$denom) }}">0.00</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- TOTAL DEL ARQUEO -->
                    <div style="background:linear-gradient(135deg, var(--s-accent-light), #e6ffe6);border:2px solid var(--s-accent);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:800;font-size:14px;color:var(--s-text);">Total Arqueo</span>
                        <span style="font-size:22px;font-weight:900;color:var(--s-accent-dark);" id="arqueo-total">S/ 0.00</span>
                        <input type="hidden" name="closing_balance" id="arqueo-total-hidden" value="0">
                    </div>

                    <div class="s-form-grid" style="margin-top:12px;">
                        <div class="s-input-group">
                            <label class="s-input-label">Notas finales / Observaciones</label>
                            <input class="s-input" name="notes" placeholder="Observaciones del cierre...">
                        </div>
                        <button class="s-btn s-btn-danger" style="justify-content:center" onclick="return confirm('¿Confirmar cierre de caja? Esta acción no se puede deshacer.')">
                            <i class="las la-door-closed"></i> Cerrar Caja y Guardar Arqueo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- HISTORIAL DE SESIONES -->
    @if($sessions->count())
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-history"></i> Sesiones Anteriores en {{ $selectedRegister->name }}</h3>
        <div style="overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Saldo Inicial</th>
                    <th>Ventas</th>
                    <th>Gastos</th>
                    <th>Saldo Final</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $s)
                <tr>
                    <td style="font-size:12px;white-space:nowrap">{{ $s->opened_at?->format('d/m H:i') }}</td>
                    <td style="font-size:12px;white-space:nowrap">{{ $s->closed_at?->format('d/m H:i') ?: '—' }}</td>
                    <td>S/ {{ number_format($s->opening_balance,2) }}</td>
                    <td style="color:var(--s-accent-dark);font-weight:700">S/ {{ number_format($s->total_sales,2) }}</td>
                    <td style="color:var(--s-danger)">S/ {{ number_format($s->total_expenses,2) }}</td>
                    <td><b>S/ {{ number_format($s->closing_balance??0,2) }}</b></td>
                    <td>
                        <span class="s-badge {{ $s->closed_at ? 's-badge-gray' : 's-badge-green' }}">
                            {{ $s->closed_at ? 'Cerrada' : 'Abierta' }}
                        </span>
                    </td>
                    <td>
                        @if($s->closed_at)
                        <a href="{{ route('seller.cash.arqueo', $s->id) }}" target="_blank" class="s-btn s-btn-ghost s-btn-xs" title="Imprimir Arqueo">
                            <i class="las la-print" style="font-size:16px;color:var(--s-primary);"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif
</div>

@push('script')
<script>
function calcArqueo() {
    var inputs = document.querySelectorAll('.arqueo-input');
    var total = 0;
    inputs.forEach(function(inp) {
        var qty = parseInt(inp.value) || 0;
        var denom = parseFloat(inp.dataset.denom);
        var sub = qty * denom;
        total += sub;
        var subEl = document.getElementById('sub-b_' + inp.name.replace('b_','').replace('.','_'));
        if (subEl) subEl.textContent = sub.toFixed(2);
    });
    document.getElementById('arqueo-total').textContent = 'S/ ' + total.toFixed(2);
    document.getElementById('arqueo-total-hidden').value = total.toFixed(2);
}
</script>
@endpush
@endsection
