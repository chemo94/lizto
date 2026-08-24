@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-calendar-check"></i></span> Reservaciones de Mesas
@endsection

@section('topbar-actions')
<a href="{{ route('seller.pos.floorplan') }}" class="s-btn s-btn-outline s-btn-sm" style="border-radius:10px;">
    <i class="las la-map-marked-alt"></i> Ver Salón
</a>
@endsection

@section('seller-content')
<div class="s-content seller-responsive-page">
    <div class="s-grid-2" style="grid-template-columns: 1fr 1.8fr; gap: 24px; align-items: start;">
        
        <!-- FORMULARIO DE RESERVACIÓN -->
        <div class="s-card" style="padding: 24px; border: 1px solid var(--s-border);">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-plus-circle" style="color: var(--s-primary); font-size: 20px;"></i> Programar Reservación
            </h3>
            
            <form method="POST" action="{{ route('seller.pos.reservations.save') }}">
                @csrf
                <input type="hidden" name="id" id="res-id">
                
                <div style="margin-bottom: 14px;">
                    <label class="s-label">Cliente / Nombre</label>
                    <input class="s-input" name="customer_name" id="res-customer-name" placeholder="Ej: Juan Pérez" required style="background: var(--s-surface-2);">
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="s-label">Celular / Teléfono</label>
                    <input class="s-input" name="customer_phone" id="res-customer-phone" placeholder="Ej: +51 987654321" style="background: var(--s-surface-2);">
                </div>

                <div class="s-form-grid-2" style="display: grid; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label class="s-label">Fecha y Hora</label>
                        <input class="s-input" type="datetime-local" name="reservation_time" id="res-time" required style="background: var(--s-surface-2);">
                    </div>
                    <div>
                        <label class="s-label">Nº Personas</label>
                        <input class="s-input" type="number" name="guests_count" id="res-guests" value="2" min="1" required style="background: var(--s-surface-2);">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="s-label">Mesa Asignada</label>
                    <select name="pos_table_id" id="res-table-id" class="s-input" style="background: var(--s-surface-2);">
                        <option value="">-- Sin mesa específica --</option>
                        @php
                            $groupedTables = $tables->groupBy(function($table) {
                                if ($table->area) {
                                    return $table->area->name;
                                }
                                if ($table->pos_area_id) {
                                    $directArea = \App\Models\PosArea::find($table->pos_area_id);
                                    if ($directArea) {
                                        return $directArea->name;
                                    }
                                }
                                return 'Sin Área';
                            });
                        @endphp
                        @foreach($groupedTables as $areaName => $areaTables)
                        <optgroup label="{{ $areaName }}">
                            @foreach($areaTables as $table)
                            <option value="{{ $table->id }}">{{ $table->name }} (Capacidad: {{ $table->capacity }} pers.)</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="s-label">Notas / Indicaciones</label>
                    <textarea class="s-input" name="notes" id="res-notes" placeholder="Ej: Mesa cerca a la ventana, alérgico a los mariscos..." style="background: var(--s-surface-2); height: 60px; resize: none; padding: 10px;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="s-label">Platos / Consumo Opcional</label>
                    <div style="max-height: 180px; overflow-y: auto; border: 1px solid var(--s-border); border-radius: 8px; padding: 12px; background: var(--s-surface-2);">
                        @forelse($products as $product)
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--s-border);">
                            <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                <input type="checkbox" name="dishes[{{ $product->id }}][selected]" value="1" class="dish-checkbox" data-product-id="{{ $product->id }}" id="dish-{{ $product->id }}" style="cursor: pointer;">
                                <label for="dish-{{ $product->id }}" style="cursor: pointer; font-size: 13px; color: var(--s-text-primary); font-weight: 500; margin: 0;">
                                    {{ $product->name }} <span style="color: var(--s-text-secondary); font-size: 11px;">(S/ {{ number_format($product->price, 2) }})</span>
                                </label>
                            </div>
                            <div class="qty-container-{{ $product->id }}" style="display: none; align-items: center; gap: 4px;">
                                <span style="font-size: 11px; color: var(--s-text-secondary);">Cant:</span>
                                <input type="number" name="dishes[{{ $product->id }}][quantity]" value="1" min="1" style="width: 50px; padding: 2px 4px; border: 1px solid var(--s-border); border-radius: 4px; background: var(--s-surface-1); text-align: center; font-size: 12px; color: var(--s-text-primary);">
                            </div>
                        </div>
                        @empty
                        <div style="font-size: 12px; color: var(--s-text-muted); text-align: center;">No hay platos registrados.</div>
                        @endforelse
                    </div>
                </div>

                <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; height: 42px; font-weight: 750;">
                    <i class="las la-save"></i> Guardar Reservación
                </button>
            </form>
        </div>

        <!-- LISTADO DE RESERVACIONES -->
        <div class="s-card" style="padding: 24px; border: 1px solid var(--s-border);">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-list" style="color: var(--s-primary); font-size: 20px;"></i> Listado de Reservas
            </h3>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--s-border); color: var(--s-text-secondary); font-weight: 700;">
                            <th style="padding: 10px;">Cliente</th>
                            <th style="padding: 10px;">Fecha / Hora</th>
                            <th style="padding: 10px;">Mesa / Pers.</th>
                            <th style="padding: 10px;">Estado</th>
                            <th style="padding: 10px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $res)
                        @php
                            $statusColors = [
                                'pending' => 's-badge-gray',
                                'confirmed' => 's-badge-blue',
                                'seated' => 's-badge-green',
                                'cancelled' => 's-badge-red',
                            ];
                            $statusTexts = [
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'seated' => 'Sentado',
                                'cancelled' => 'Cancelado',
                            ];
                        @endphp
                        <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-surface-2)'" onmouseout="this.style.background=''">
                            <td style="padding: 12px 10px;">
                                <strong>{{ $res->customer_name }}</strong>
                                @if($res->customer_phone)
                                <div style="font-size: 11px; color: var(--s-text-muted); margin-top: 2px;"><i class="las la-phone"></i> {{ $res->customer_phone }}</div>
                                @endif
                                @if($res->notes)
                                <div style="font-size: 10.5px; color: var(--s-danger-text); font-style: italic; margin-top: 4px;"><i class="las la-info-circle"></i> {{ $res->notes }}</div>
                                @endif
                                
                                @if(!empty($res->dishes))
                                <div style="font-size: 11px; margin-top: 6px; padding: 6px 10px; background: var(--s-surface-1); border-radius: 6px; border: 1px dashed var(--s-border);">
                                    <strong style="color: var(--s-text-primary); font-size: 10px; display: block; margin-bottom: 2px;"><i class="las la-utensils"></i> Platos opcionales:</strong>
                                    <ul style="margin: 0; padding-left: 12px; list-style-type: disc;">
                                        @foreach($res->dishes as $dish)
                                            @php
                                                $prod = $products->firstWhere('id', $dish['product_id']);
                                            @endphp
                                            @if($prod)
                                                <li style="color: var(--s-text-secondary); font-size: 10.5px;">
                                                    {{ $prod->name }} (x{{ $dish['quantity'] }})
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                {{ $res->reservation_time->format('d/m/Y') }}
                                <div style="font-weight: 700; color: var(--s-text-primary); margin-top: 2px;">{{ $res->reservation_time->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 12px 10px;">
                                <div>
                                    <strong>{{ $res->table?->name ?: 'Sin asignar' }}</strong>
                                    @if($res->table?->area)
                                    <span style="font-size: 11px; color: var(--s-text-muted);">({{ $res->table->area->name }})</span>
                                    @endif
                                </div>
                                <div style="font-size: 11px; color: var(--s-text-secondary); margin-top: 2px;">{{ $res->guests_count }} personas</div>
                            </td>
                            <td style="padding: 12px 10px;">
                                <span class="s-badge {{ $statusColors[$res->status] ?? 's-badge-gray' }}" style="font-weight: 700;">
                                    {{ $statusTexts[$res->status] ?? $res->status }}
                                </span>
                            </td>
                            <td style="padding: 12px 10px; text-align: right;">
                                <div style="display: flex; gap: 4px; justify-content: flex-end; align-items: center;">
                                    <button class="s-btn s-btn-ghost s-btn-xs" style="padding: 4px;" title="Editar Reserva" onclick="editReservation({{ json_encode($res) }})">
                                        <i class="las la-edit" style="font-size: 16px; color: var(--s-info);"></i>
                                    </button>
                                    
                                    @if($res->status === 'pending' || $res->status === 'confirmed')
                                    <form method="POST" action="{{ route('seller.pos.reservations.status', $res->id) }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="status" value="seated">
                                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="padding: 4px;" title="Marcar como Sentado">
                                            <i class="las la-user-check" style="font-size: 16px; color: var(--s-success);"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($res->status !== 'cancelled' && $res->status !== 'seated')
                                    <form method="POST" action="{{ route('seller.pos.reservations.status', $res->id) }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="padding: 4px;" title="Cancelar Reserva">
                                            <i class="las la-ban" style="font-size: 16px; color: var(--s-danger);"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px 10px; color: var(--s-text-muted);">
                                <i class="las la-calendar-times" style="font-size: 40px; display: block; margin-bottom: 8px; color: var(--s-border);"></i>
                                No hay reservaciones registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('dish-checkbox')) {
        const productId = e.target.dataset.productId;
        const qtyContainer = document.querySelector('.qty-container-' + productId);
        if (qtyContainer) {
            qtyContainer.style.display = e.target.checked ? 'flex' : 'none';
        }
    }
});

function editReservation(res) {
    document.getElementById('res-id').value = res.id;
    document.getElementById('res-customer-name').value = res.customer_name;
    document.getElementById('res-customer-phone').value = res.customer_phone || '';
    document.getElementById('res-guests').value = res.guests_count;
    document.getElementById('res-table-id').value = res.pos_table_id || '';
    document.getElementById('res-notes').value = res.notes || '';
    
    // Format datetime-local string
    if(res.reservation_time) {
        var date = new Date(res.reservation_time);
        var offset = date.getTimezoneOffset() * 60000;
        var localISOTime = (new Date(date.getTime() - offset)).toISOString().slice(0, 16);
        document.getElementById('res-time').value = localISOTime;
    }

    // Reset all checkboxes and quantity containers
    document.querySelectorAll('.dish-checkbox').forEach(cb => {
        cb.checked = false;
        const qtyContainer = document.querySelector('.qty-container-' + cb.dataset.productId);
        if (qtyContainer) qtyContainer.style.display = 'none';
        const qtyInput = qtyContainer ? qtyContainer.querySelector('input') : null;
        if (qtyInput) qtyInput.value = 1;
    });

    // Populate dishes
    if (res.dishes) {
        let dishesData = typeof res.dishes === 'string' ? JSON.parse(res.dishes) : res.dishes;
        if (Array.isArray(dishesData)) {
            dishesData.forEach(item => {
                const cb = document.getElementById('dish-' + item.product_id);
                if (cb) {
                    cb.checked = true;
                    const qtyContainer = document.querySelector('.qty-container-' + item.product_id);
                    if (qtyContainer) {
                        qtyContainer.style.display = 'flex';
                        const qtyInput = qtyContainer.querySelector('input');
                        if (qtyInput) qtyInput.value = item.quantity;
                    }
                }
            });
        }
    }
}
</script>
@endpush
