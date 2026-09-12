@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-receipt"></i></span> Gastos
@endsection

@section('seller-content')
<div class="s-content">
    @php $expenseTotal = $expenses->sum('amount'); @endphp
    <section class="module-hero expenses"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Finanzas &nbsp;/&nbsp; Gastos</div><h2><i class="las la-receipt"></i> Control de Gastos</h2><p>Registra egresos y analiza su distribución por categoría y método de pago.</p></div><div class="module-hero-stats"><div><b>{{ $expenses->count() }}</b><small>Registros visibles</small></div><div><b>{{ $catTotals->count() }}</b><small>Categorías</small></div><div><b>S/ {{ number_format($expenseTotal,0) }}</b><small>Total visible</small></div></div></section>

    <!-- FORM -->
    <div class="s-card seller-work-card" style="margin-bottom:14px">
        <h3 class="s-card-title"><i class="las la-plus-circle"></i> Registrar Nuevo Gasto</h3>
        <form method="POST" action="{{ route('seller.expenses.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:flex-end">
                <div class="s-input-group">
                    <label class="s-input-label">Categoría *</label>
                    <select class="s-input" name="category" required>
                        @foreach($categories as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Monto (S/) *</label>
                    <input class="s-input" type="number" name="amount" step="0.01" placeholder="0.00" required>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Proveedor</label>
                    <input class="s-input" name="provider" placeholder="Nombre del proveedor">
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">N° Factura</label>
                    <input class="s-input" name="invoice_number" placeholder="F001-123">
                </div>
                <div class="s-input-group" style="grid-column:span 2">
                    <label class="s-input-label">Descripción *</label>
                    <input class="s-input" name="description" placeholder="Descripción del gasto" required>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Método de pago</label>
                    <select class="s-input" name="payment_method">
                        <option value="cash">💵 Efectivo</option>
                        <option value="transfer">🏦 Transferencia</option>
                        <option value="card">💳 Tarjeta</option>
                        <option value="yape">📱 Yape / Plin</option>
                    </select>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Fecha</label>
                    <input class="s-input" type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div style="display:flex;align-items:flex-end">
                    <button class="s-btn s-btn-primary" style="width:100%;justify-content:center">
                        <i class="las la-plus"></i> Registrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- TOTALES POR CATEGORÍA -->
    @if($catTotals->count())
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
        @foreach($catTotals as $cat => $total)
        <div class="s-stat" style="flex:0 0 auto;padding:14px 18px">
            <div class="s-stat-icon red"><i class="las la-tag"></i></div>
            <div>
                <strong style="font-size:20px">S/ {{ number_format($total,2) }}</strong>
                <small>{{ $categories[$cat] ?? $cat }}</small>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- HISTORIAL -->
    <div class="s-card seller-work-card">
        <h3 class="s-card-title"><i class="las la-list"></i> Historial de Gastos</h3>
        <div style="overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Proveedor</th>
                    <th>N° Factura</th>
                    <th>Descripción</th>
                    <th>Método</th>
                    <th>Monto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $e)
                <tr>
                    <td style="font-size:12px;white-space:nowrap;color:var(--s-text-2)">{{ $e->expense_date?->format('d/m/Y') ?: $e->created_at->format('d/m/Y') }}</td>
                    <td>
                        <span class="s-badge s-badge-red">{{ $categories[$e->category] ?? $e->category }}</span>
                    </td>
                    <td style="color:var(--s-text-2)">{{ $e->provider ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--s-text-3)">{{ $e->invoice_number ?: '—' }}</td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $e->description }}</td>
                    <td>
                        @php
                            $methods = ['cash'=>'💵 Efectivo','transfer'=>'🏦 Transfer.','card'=>'💳 Tarjeta','yape'=>'📱 Yape'];
                        @endphp
                        <span style="font-size:12px;color:var(--s-text-3)">{{ $methods[$e->payment_method ?? 'cash'] ?? '—' }}</span>
                    </td>
                    <td><b style="color:var(--s-danger)">S/ {{ number_format($e->amount,2) }}</b></td>
                    <td>
                        <form method="POST" action="{{ route('seller.expenses.delete', $e->id) }}" onsubmit="return confirm('¿Eliminar este gasto?')">
                            @csrf
                            <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Eliminar">
                                <i class="las la-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="s-empty">
                            <i class="las la-receipt"></i>
                            <p>Sin gastos registrados</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
