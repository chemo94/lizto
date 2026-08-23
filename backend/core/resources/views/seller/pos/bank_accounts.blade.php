@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-university"></i></span> Caja y Bancos
@endsection

@section('seller-content')
<div class="s-content">
    @php $bankingBalance = $accounts->sum('balance'); @endphp
    <section class="module-hero banking"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Finanzas &nbsp;/&nbsp; Bancos</div><h2><i class="las la-university"></i> Cuentas y Monederos</h2><p>Administra bancos, billeteras digitales, terminales POS y movimientos.</p></div><div class="module-hero-stats"><div><b>{{ $accounts->count() }}</b><small>Cuentas</small></div><div><b>{{ $accounts->where('status','active')->count() }}</b><small>Activas</small></div><div><b>S/ {{ number_format($bankingBalance,0) }}</b><small>Saldo total</small></div></div></section>

    <div style="display:flex;gap:8px;margin-bottom:14px;border-bottom:1px solid var(--s-border);padding-bottom:10px;overflow-x:auto">
        <a href="{{ route('seller.cash') }}" style="text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;color:{{ request()->routeIs('seller.cash') ? '#fff' : 'var(--s-text-2)' }};background:{{ request()->routeIs('seller.cash') ? 'var(--s-primary)' : 'transparent' }};border:{{ request()->routeIs('seller.cash') ? 'none' : '1px solid var(--s-border)' }}">
            <i class="las la-cash-register" style="font-size:16px"></i> Caja Chica / POS
        </a>
        <a href="{{ route('seller.pos.bank_accounts') }}" style="text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;color:{{ request()->routeIs('seller.pos.bank_accounts') ? '#fff' : 'var(--s-text-2)' }};background:{{ request()->routeIs('seller.pos.bank_accounts') ? 'var(--s-primary)' : 'transparent' }};border:{{ request()->routeIs('seller.pos.bank_accounts') ? 'none' : '1px solid var(--s-border)' }}">
            <i class="las la-university" style="font-size:16px"></i> Cuentas Bancarias / Monederos
        </a>
    </div>

    <div class="banking-workspace" style="display: grid; grid-template-columns: 360px 1fr; gap: 14px; align-items: start;">
        
        <!-- REGISTRO Y TRANSACCIONES MANUALES -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- REGISTRAR CUENTA / MONEDERO -->
            <div class="s-card">
                <h3 id="form-title" style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-plus-circle" style="color: var(--s-primary); font-size: 20px;"></i> Registrar Cuenta o POS
                </h3>
                
                <form id="account-form" method="POST" action="{{ route('seller.pos.bank_accounts.store') }}">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label class="s-label">Nombre Identificador</label>
                            <input class="s-input" name="name" id="account-name" placeholder="Ej: Yape Principal, BCP Ahorros" required autocomplete="off">
                        </div>

                        <div>
                            <label class="s-label">Tipo de Cuenta</label>
                            <select class="s-input" name="type" id="account-type" required onchange="handleTypeChange()">
                                <option value="wallet">Billetera Digital (Yape / Plin)</option>
                                <option value="bank">Cuenta Bancaria (Transferencias)</option>
                                <option value="pos_card">Terminal POS (Tarjetero Visa/MC)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="s-label" id="bank-label">Banco / Proveedor</label>
                            <input class="s-input" name="bank_name" id="account-bank" placeholder="Ej: Yape, Plin, BCP, Niubiz" required autocomplete="off">
                        </div>

                        <div>
                            <label class="s-label" id="number-label">Celular o N° Cuenta</label>
                            <input class="s-input" name="account_number" id="account-number" placeholder="Ej: 987654321, 191-xxxxxx" autocomplete="off">
                        </div>

                        <div id="status-group" style="display: none;">
                            <label class="s-label">Estado</label>
                            <select class="s-input" name="status" id="account-status">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <button type="submit" class="s-btn s-btn-primary" style="flex: 1; justify-content: center; height: 42px;">
                                <i class="las la-save" style="font-size: 18px;"></i> Guardar
                            </button>
                            <button type="button" id="btn-cancel" class="s-btn s-btn-outline" style="display: none; height: 42px;" onclick="resetForm()">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- REGISTRAR MOVIMIENTO MANUAL -->
            @if($accounts->isNotEmpty())
            <div class="s-card" style="border: 1px solid var(--s-border);">
                <h3 style="margin-bottom: 15px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-exchange-alt" style="color: var(--s-info); font-size: 20px;"></i> Operación Manual (Depósito/Retiro)
                </h3>
                
                <form method="POST" action="{{ route('seller.pos.bank_accounts.transaction') }}">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label class="s-label">Seleccionar Cuenta</label>
                            <select class="s-input" name="pos_bank_account_id" required>
                                @foreach($accounts->where('status', 'active') as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ strtoupper($acc->bank_name) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="s-label">Tipo de Mov.</label>
                                <select class="s-input" name="type" required>
                                    <option value="cash_in">Ingreso / Depósito (+)</option>
                                    <option value="cash_out">Egreso / Retiro (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="s-label">Monto (S/)</label>
                                <input class="s-input" type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" required>
                            </div>
                        </div>

                        <div>
                            <label class="s-label">Descripción</label>
                            <input class="s-input" name="description" placeholder="Ej: Depósito de arqueo de caja" required autocomplete="off">
                        </div>

                        <button type="submit" class="s-btn s-btn-outline" style="justify-content: center; font-weight: 700; width: 100%; height: 38px; border-color: var(--s-info); color: var(--s-info);">
                            <i class="las la-check"></i> Registrar Movimiento
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- LISTADO Y MOVIMIENTOS -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- TARJETAS DE CUENTAS Y BALANCES -->
            <div class="s-card">
                <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-wallet" style="color: var(--s-primary); font-size: 20px;"></i> Saldos y Cuentas Activas
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                    @forelse($accounts as $acc)
                    <div class="s-card" style="padding: 16px; border: 1px solid var(--s-border); position: relative; background: var(--s-surface-2); display: flex; flex-direction: column; justify-content: space-between; min-height: 130px; box-shadow: none;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                @if($acc->type === 'wallet')
                                <span class="s-badge s-badge-purple" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Yape / Plin</span>
                                @elseif($acc->type === 'pos_card')
                                <span class="s-badge s-badge-orange" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Tarjetero POS</span>
                                @else
                                <span class="s-badge s-badge-blue" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Banco</span>
                                @endif
                                
                                @if($acc->status === 'inactive')
                                <span class="s-badge s-badge-gray" style="font-size: 8px;">Inactivo</span>
                                @endif
                            </div>
                            
                            <h4 style="margin: 0; font-weight: 800; font-size: 14px; color: var(--s-text-primary);">{{ $acc->name }}</h4>
                            <span style="font-size: 11px; color: var(--s-text-muted);">
                                {{ $acc->bank_name }} @if($acc->account_number) - {{ $acc->account_number }} @endif
                            </span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 14px; border-top: 1px dashed var(--s-border); padding-top: 10px;">
                            <div>
                                <span style="font-size: 10px; color: var(--s-text-muted); display: block; text-transform: uppercase; font-weight: 700; letter-spacing: 0.2px;">Saldo Disponible</span>
                                <b style="font-size: 16px; color: var(--s-primary); font-weight: 900;">S/ {{ number_format($acc->balance, 2) }}</b>
                            </div>
                            
                            <div style="display: flex; gap: 4px;">
                                <button class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-primary); padding: 4px;" onclick="editAccount({{ json_encode($acc) }})">
                                    <i class="las la-edit" style="font-size: 16px;"></i>
                                </button>
                                <form method="POST" action="{{ route('seller.pos.bank_accounts.delete', $acc->id) }}" onsubmit="return confirm('¿Eliminar esta cuenta? Si tiene transacciones se desactivará.')" style="display:inline">
                                    @csrf
                                    <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger); padding: 4px;">
                                        <i class="las la-trash" style="font-size: 16px;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div style="grid-column: 1/-1; text-align: center; padding: 30px; color: var(--s-text-muted);">
                        <i class="las la-university" style="font-size: 40px; color: var(--s-border); display: block; margin-bottom: 10px;"></i>
                        No hay cuentas bancarias o billeteras digitales registradas.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- DETALLE DE MOVIMIENTOS -->
            <div class="s-card">
                <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-history" style="color: var(--s-primary); font-size: 20px;"></i> Últimos Movimientos Bancarios
                </h3>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                                <th style="padding: 10px 14px;">Fecha</th>
                                <th style="padding: 10px 14px;">Cuenta / Banco</th>
                                <th style="padding: 10px 14px;">Concepto / Descripción</th>
                                <th style="padding: 10px 14px;">Tipo</th>
                                <th style="padding: 10px 14px; text-align: right;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accountTransactions as $tx)
                            <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background=''">
                                <td style="padding: 12px 14px; color: var(--s-text-secondary);">
                                    {{ $tx->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: var(--s-text-primary);">{{ $tx->bankAccount->name }}</div>
                                    <span style="font-size: 10px; color: var(--s-text-muted);">{{ strtoupper($tx->bankAccount->bank_name) }}</span>
                                </td>
                                <td style="padding: 12px 14px; color: var(--s-text-primary);">
                                    {{ $tx->description }}
                                    @if($tx->order)
                                    <span style="font-weight: 800; font-size: 11px; color: var(--s-primary); margin-left: 4px;">#{{ $tx->order->order_no }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    @if($tx->type === 'sale' || $tx->type === 'cash_in')
                                    <span class="s-badge s-badge-green" style="font-size: 9px; padding: 2px 6px;">INGRESO</span>
                                    @else
                                    <span class="s-badge s-badge-red" style="font-size: 9px; padding: 2px 6px;">EGRESO</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; text-align: right; font-weight: 800; font-size: 14px; color: {{ ($tx->type==='sale'||$tx->type==='cash_in') ? 'var(--s-success-text)' : 'var(--s-danger-text)' }}">
                                    {{ ($tx->type==='sale'||$tx->type==='cash_in') ? '+' : '-' }} S/ {{ number_format($tx->amount, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px 10px; color: var(--s-text-muted);">
                                    No hay movimientos registrados en las cuentas bancarias o billeteras.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 15px;">
                    {{ $accountTransactions->links() }}
                </div>
            </div>

        </div>

    </div>
</div>

@push('script')
<script>
function handleTypeChange() {
    let type = document.getElementById('account-type').value;
    let bankLabel = document.getElementById('bank-label');
    let numberLabel = document.getElementById('number-label');
    
    if (type === 'wallet') {
        bankLabel.textContent = 'Proveedor de Billetera';
        document.getElementById('account-bank').placeholder = 'Yape o Plin';
        numberLabel.textContent = 'Número Celular';
        document.getElementById('account-number').placeholder = 'Ej: 987654321';
    } else if (type === 'pos_card') {
        bankLabel.textContent = 'Proveedor POS';
        document.getElementById('account-bank').placeholder = 'Ej: Niubiz, Izipay, Vendemás';
        numberLabel.textContent = 'N° Serie Terminal (Opcional)';
        document.getElementById('account-number').placeholder = 'Ej: SN-872346';
    } else {
        bankLabel.textContent = 'Banco';
        document.getElementById('account-bank').placeholder = 'Ej: BCP, BBVA, Interbank';
        numberLabel.textContent = 'Número de Cuenta / CCI';
        document.getElementById('account-number').placeholder = 'Ej: 191-xxxxxx-xxx';
    }
}

function editAccount(acc) {
    document.getElementById('form-title').innerHTML = '<i class="las la-edit" style="color:var(--s-warning); font-size: 20px;"></i> Editar Cuenta / POS';
    document.getElementById('account-form').action = "{{ route('seller.pos.bank_accounts.update', '__ID__') }}".replace('__ID__', acc.id);
    
    document.getElementById('account-name').value = acc.name;
    document.getElementById('account-type').value = acc.type;
    document.getElementById('account-bank').value = acc.bank_name || '';
    document.getElementById('account-number').value = acc.account_number || '';
    document.getElementById('account-status').value = acc.status;
    
    handleTypeChange();
    
    document.getElementById('status-group').style.display = 'block';
    document.getElementById('btn-cancel').style.display = 'block';
}

function resetForm() {
    document.getElementById('form-title').innerHTML = '<i class="las la-plus-circle" style="color:var(--s-primary); font-size: 20px;"></i> Registrar Cuenta o POS';
    document.getElementById('account-form').action = "{{ route('seller.pos.bank_accounts.store') }}";
    document.getElementById('account-form').reset();
    
    handleTypeChange();
    
    document.getElementById('status-group').style.display = 'none';
    document.getElementById('btn-cancel').style.display = 'none';
}

// Initial placeholder setup
handleTypeChange();
</script>
@endpush
@endsection
