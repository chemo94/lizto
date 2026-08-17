@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-wallet"></i></span> Planilla y Pago de Nómina - RR.HH
@endsection

@section('seller-content')
<div class="s-content">
    
    <!-- TOP ACTIONS CARD: CALCULATOR -->
    <div class="s-card" style="margin-bottom: 24px;">
        <h3 style="margin-bottom: 15px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-calculator" style="color: var(--s-primary); font-size: 20px;"></i> Calcular Planilla del Periodo
        </h3>
        
        <form method="POST" action="{{ route('seller.pos.hr.payroll.calculate') }}">
            @csrf
            <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label class="s-label">Fecha de Inicio del Periodo</label>
                    <input type="date" name="period_start" class="s-input" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label class="s-label">Fecha de Fin del Periodo</label>
                    <input type="date" name="period_end" class="s-input" value="{{ now()->endOfMonth()->format('Y-m-d') }}" required>
                </div>
                <button type="submit" class="s-btn s-btn-primary" style="height: 42px; font-weight: 700; padding: 0 24px;">
                    <i class="las la-sync-alt"></i> Procesar Cálculo
                </button>
            </div>
        </form>
        <p style="font-size: 11px; color: var(--s-text-muted); margin-top: 10px;">
            * El cálculo considera el tipo de sueldo de cada empleado (Mensual prorrateado, Diario por días asistidos, Horario por horas acumuladas de entrada/salida) y sumará las comisiones obtenidas por pedidos pagados y asociados al empleado en el mismo rango de fechas.
        </p>
    </div>

    <!-- MAIN LISTING TABLE -->
    <div class="s-card">
        <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-file-invoice-dollar" style="color: var(--s-primary); font-size: 20px;"></i> Historial de Cálculos y Pagos de Planilla
        </h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                        <th style="padding: 10px 14px;">Periodo</th>
                        <th style="padding: 10px 14px;">Empleado / Cargo</th>
                        <th style="padding: 10px 14px;">Sueldo Base Earned</th>
                        <th style="padding: 10px 14px;">Comisiones POS</th>
                        <th style="padding: 10px 14px;">Bonos / Dctos</th>
                        <th style="padding: 10px 14px;">Total Neto</th>
                        <th style="padding: 10px 14px;">Estado de Pago</th>
                        <th style="padding: 10px 14px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $pay)
                    <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background=''">
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 600;">{{ $pay->period_start->format('d/m/Y') }} al {{ $pay->period_end->format('d/m/Y') }}</div>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: var(--s-text-primary);">{{ $pay->staff->name }}</div>
                            <span class="s-badge s-badge-gray" style="font-size: 9px; padding: 1px 5px; text-transform: uppercase;">
                                {{ $pay->staff->position }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; color: var(--s-text-secondary);">
                            S/ {{ number_format($pay->base_salary_earned, 2) }}
                        </td>
                        <td style="padding: 12px 14px; color: var(--s-accent-dark); font-weight: 600;">
                            S/ {{ number_format($pay->commissions_earned, 2) }}
                        </td>
                        <td style="padding: 12px 14px; color: var(--s-text-muted);">
                            @if($pay->payment_status === 'paid')
                                <span style="color: var(--s-success);">+S/ {{ number_format($pay->bonuses, 2) }}</span> / 
                                <span style="color: var(--s-danger);">-S/ {{ number_format($pay->deductions, 2) }}</span>
                            @else
                                <span style="color: var(--s-text-muted);">Pendiente de pago</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-weight: 700; font-size: 14px; color: var(--s-text-primary);">
                            S/ {{ number_format($pay->net_salary, 2) }}
                        </td>
                        <td style="padding: 12px 14px;">
                            @if($pay->payment_status === 'paid')
                                <span class="s-badge s-badge-green" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="las la-check-circle"></i> Pagado
                                </span>
                                <div style="font-size: 10px; color: var(--s-text-muted); margin-top: 2px;">
                                    {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '' }} ({{ $pay->payment_method }})
                                </div>
                            @else
                                <span class="s-badge s-badge-yellow" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="las la-hourglass-half"></i> Pendiente
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                @if($pay->payment_status === 'pending')
                                <button type="button" class="s-btn s-btn-xs s-btn-primary" 
                                        onclick="openPayModal({{ json_encode($pay) }}, {{ json_encode($pay->staff) }})">
                                    <i class="las la-money-bill-wave"></i> Pagar
                                </button>
                                @else
                                <a href="{{ route('seller.pos.hr.payroll.pdf', $pay->id) }}" target="_blank" class="s-btn s-btn-xs s-btn-outline">
                                    <i class="las la-file-pdf"></i> Boleta PDF
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 45px 10px; color: var(--s-text-muted);">
                            <i class="las la-wallet" style="font-size: 48px; display: block; margin-bottom: 12px; color: var(--s-border);"></i>
                            No se han calculado planillas de nóminas para este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 15px;">
            {{ $payrolls->links() }}
        </div>
    </div>
</div>

<!-- PAY MODAL -->
<div id="payModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999;">
    <div class="s-card" style="width: 500px; max-width: 90%; position: relative; animation: slideDown 0.3s ease-out;">
        <button type="button" onclick="closePayModal()" style="position: absolute; top: 15px; right: 15px; border: none; background: none; font-size: 24px; color: var(--s-text-muted); cursor: pointer;">&times;</button>
        
        <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 18px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-hand-holding-usd" style="color: var(--s-primary); font-size: 24px;"></i> Registrar Pago de Nómina
        </h3>
        
        <form id="payForm" method="POST" action="">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="padding: 10px 14px; background: var(--s-bg-light); border-radius: 6px;">
                    <div style="font-weight: 700; color: var(--s-text-primary);" id="modalStaffName">Trabajador</div>
                    <div style="font-size: 11px; color: var(--s-text-muted);" id="modalPeriod">Periodo: -</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="s-label">Sueldo Base Ganado</label>
                        <input class="s-input" type="text" id="modalBase" readonly style="background: var(--s-bg-light); font-weight: 600;">
                    </div>
                    <div>
                        <label class="s-label">Comisiones POS</label>
                        <input class="s-input" type="text" id="modalCommissions" readonly style="background: var(--s-bg-light); font-weight: 600;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="s-label" style="color: var(--s-success-dark);">Adicionales / Bonos (+)</label>
                        <input class="s-input" type="number" step="0.01" min="0" name="bonuses" id="inputBonuses" value="0.00" onkeyup="updateNetTotal()" onchange="updateNetTotal()">
                    </div>
                    <div>
                        <label class="s-label" style="color: var(--s-danger);">Descuentos / Adelantos (-)</label>
                        <input class="s-input" type="number" step="0.01" min="0" name="deductions" id="inputDeductions" value="0.00" onkeyup="updateNetTotal()" onchange="updateNetTotal()">
                    </div>
                </div>

                <div>
                    <label class="s-label">Método de Pago</label>
                    <select class="s-input" name="payment_method" required>
                        <option value="cash">Efectivo (Registra egreso en caja POS)</option>
                        <option value="transfer">Transferencia Bancaria</option>
                        <option value="bank_deposit">Depósito en Cuenta</option>
                    </select>
                </div>

                <div>
                    <label class="s-label">Notas / Observaciones</label>
                    <textarea class="s-input" name="notes" rows="2" placeholder="Detalle de bonos o descuentos..."></textarea>
                </div>

                <div style="padding: 12px 14px; background: var(--s-primary-light); border-radius: 6px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--s-primary);">
                    <span style="font-weight: 700; color: var(--s-primary-dark); font-size: 14px;">Total Neto a Pagar:</span>
                    <span style="font-weight: 800; color: var(--s-primary-dark); font-size: 18px;" id="modalNetTotal">S/ 0.00</span>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="s-btn s-btn-primary" style="flex: 1; justify-content: center; height: 42px;">
                        <i class="las la-check"></i> Confirmar y Registrar Pago
                    </button>
                    <button type="button" class="s-btn s-btn-outline" onclick="closePayModal()" style="height: 42px;">
                        Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('script')
<script>
    let currentBase = 0;
    let currentCommissions = 0;

    function openPayModal(payroll, staff) {
        document.getElementById('payForm').action = "{{ route('seller.pos.hr.payroll.pay', '__ID__') }}".replace('__ID__', payroll.id);
        
        document.getElementById('modalStaffName').textContent = staff.name + ' (' + staff.position.toUpperCase() + ')';
        
        // format date range
        let start = new Date(payroll.period_start).toLocaleDateString('es-PE');
        let end = new Date(payroll.period_end).toLocaleDateString('es-PE');
        document.getElementById('modalPeriod').textContent = 'Periodo: ' + start + ' al ' + end;
        
        currentBase = parseFloat(payroll.base_salary_earned || 0);
        currentCommissions = parseFloat(payroll.commissions_earned || 0);
        
        document.getElementById('modalBase').value = 'S/ ' + currentBase.toFixed(2);
        document.getElementById('modalCommissions').value = 'S/ ' + currentCommissions.toFixed(2);
        
        document.getElementById('inputBonuses').value = '0.00';
        document.getElementById('inputDeductions').value = '0.00';
        
        updateNetTotal();
        
        document.getElementById('payModal').style.display = 'flex';
    }

    function closePayModal() {
        document.getElementById('payModal').style.display = 'none';
    }

    function updateNetTotal() {
        let bonuses = parseFloat(document.getElementById('inputBonuses').value || 0);
        let deductions = parseFloat(document.getElementById('inputDeductions').value || 0);
        
        let net = currentBase + currentCommissions + bonuses - deductions;
        if(net < 0) net = 0;
        
        document.getElementById('modalNetTotal').textContent = 'S/ ' + net.toFixed(2);
    }
</script>
<style>
@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
@endpush
@endsection
