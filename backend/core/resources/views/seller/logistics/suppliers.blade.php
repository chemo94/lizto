@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-truck"></i></span> Gestión de Proveedores
@endsection

@section('seller-content')
<div class="s-content">

    <!-- FORMULARIO DE AGREGAR PROVEEDOR -->
    <div class="s-card" style="margin-bottom:20px">
        <h3 class="s-card-title"><i class="las la-plus-circle"></i> Registrar Proveedor</h3>
        <form method="POST" action="{{ route('seller.logistics.suppliers.store') }}" class="s-form-grid s-form-grid-3">
            @csrf
            <div class="s-input-group">
                <label class="s-input-label">Nombre / Razón Social *</label>
                <input class="s-input" id="supplier-name" name="name" placeholder="Ej: Distribuidora Norte SAC" required>
            </div>
            
            <div class="s-input-group">
                <label class="s-input-label">Documento (RUC/DNI)</label>
                @include('seller.partials.sunat_lookup', [
                    'prefix'      => 'supplier',
                    'defaultType' => '6',
                    'nameTarget'  => 'supplier-name',
                    'tpdocName'   => 'document_type',
                    'numdocName'  => 'document_number',
                ])
            </div>

            <div class="s-input-group">
                <label class="s-input-label">Persona de Contacto</label>
                <input class="s-input" name="contact_person" placeholder="Nombre de contacto">
            </div>

            <div class="s-input-group">
                <label class="s-input-label">Teléfono</label>
                <input class="s-input" name="phone" placeholder="Ej: 999888777">
            </div>

            <div class="s-input-group">
                <label class="s-input-label">Correo Electrónico</label>
                <input class="s-input" type="email" name="email" placeholder="proveedor@empresa.com">
            </div>

            <div class="s-input-group">
                <label class="s-input-label">Dirección</label>
                <input class="s-input" name="address" placeholder="Av. Principal 123">
            </div>

            <!-- Calificaciones -->
            <div class="s-input-group">
                <label class="s-input-label">Calidad (1-5 Estrellas)</label>
                <select class="s-input" name="rating_quality">
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4" selected>⭐⭐⭐⭐ Bueno</option>
                    <option value="3">⭐⭐⭐ Regular</option>
                    <option value="2">⭐⭐ Malo</option>
                    <option value="1">⭐ Pésimo</option>
                </select>
            </div>

            <div class="s-input-group">
                <label class="s-input-label">Tiempo Entrega (1-5 Estrellas)</label>
                <select class="s-input" name="rating_delivery_time">
                    <option value="5">⭐⭐⭐⭐⭐ Muy rápido</option>
                    <option value="4" selected>⭐⭐⭐⭐ Rápido</option>
                    <option value="3">⭐⭐⭐ Promedio</option>
                    <option value="2">⭐⭐ Lento</option>
                    <option value="1">⭐ Muy lento</option>
                </select>
            </div>

            <div class="s-input-group" style="display:flex;align-items:flex-end">
                <button class="s-btn s-btn-primary" style="width:100%">
                    <i class="las la-plus"></i> Guardar Proveedor
                </button>
            </div>
        </form>
    </div>

    <!-- LISTADO -->
    <div class="s-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-list"></i> Proveedores Registrados</h3>
            <span class="s-badge s-badge-blue">{{ count($suppliers) }} registros</span>
        </div>

        @if(count($suppliers) > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:16px">
            @foreach($suppliers as $s)
            <div class="s-card" style="border:1.5px solid var(--s-border);padding:20px;position:relative">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h4 style="margin:0 0 6px;color:var(--s-text)">{{ $s->name }}</h4>
                        <span style="font-size:12px;color:var(--s-text-3)">RUC/DNI: {{ $s->document_number ?: '—' }}</span>
                    </div>
                    <div style="display:flex;gap:4px">
                        <button class="s-btn s-btn-outline s-btn-xs" onclick="openEditModal({{ json_encode($s) }})">
                            <i class="las la-edit"></i>
                        </button>
                        <form method="POST" action="{{ route('seller.logistics.suppliers.delete', $s->id) }}" onsubmit="return confirm('¿Eliminar proveedor?')">
                            @csrf
                            <button class="s-btn s-btn-danger s-btn-xs">
                                <i class="las la-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <hr class="s-divider" style="margin:12px 0">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;color:var(--s-text-2)">
                    <div><i class="las la-user"></i> <b>Contacto:</b> {{ $s->contact_person ?: '—' }}</div>
                    <div><i class="las la-phone"></i> <b>Tel:</b> {{ $s->phone ?: '—' }}</div>
                    <div><i class="las la-envelope"></i> <b>Email:</b> {{ $s->email ?: '—' }}</div>
                    <div><i class="las la-map-marker"></i> <b>Dirección:</b> {{ $s->address ?: '—' }}</div>
                </div>

                <hr class="s-divider" style="margin:12px 0">

                <!-- Desempeño / Ratings -->
                <div style="font-size:11px;color:var(--s-text-3);display:flex;flex-wrap:wrap;gap:15px">
                    <span>
                        <b>Calidad:</b> 
                        <span style="color:#f59e0b">{{ str_repeat('★', $s->rating_quality ?? 4) }}{{ str_repeat('☆', 5 - ($s->rating_quality ?? 4)) }}</span>
                    </span>
                    <span>
                        <b>Entrega:</b> 
                        <span style="color:#f59e0b">{{ str_repeat('★', $s->rating_delivery_time ?? 4) }}{{ str_repeat('☆', 5 - ($s->rating_delivery_time ?? 4)) }}</span>
                    </span>
                    <span>
                        <b>Compras:</b> <span class="s-badge s-badge-green">{{ $s->total_purchases ?? 0 }}</span>
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="s-empty">
            <i class="las la-truck" style="font-size:48px"></i>
            <p>Aún no hay proveedores registrados.</p>
        </div>
        @endif
    </div>
</div>

<!-- MODAL EDITAR PROVEEDOR -->
<div class="s-modal" id="edit-modal">
    <div class="s-modal-bg" onclick="closeEditModal()"></div>
    <div class="s-modal-box" style="max-width:600px">
        <div class="s-modal-head">
            <h3 class="s-modal-title">Editar Proveedor</h3>
            <button class="s-modal-close" onclick="closeEditModal()">✕</button>
        </div>
        <div class="s-modal-body">
            <form id="edit-form" method="POST" action="">
                @csrf
                <div class="s-form-grid s-form-grid-2">
                    <div class="s-input-group">
                        <label class="s-input-label">Nombre / Razón Social *</label>
                        <input class="s-input" id="edit-name" name="name" required>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Número de Documento</label>
                        <input class="s-input" id="edit-doc" name="document_number">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Persona de Contacto</label>
                        <input class="s-input" id="edit-contact" name="contact_person">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Teléfono</label>
                        <input class="s-input" id="edit-phone" name="phone">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Correo Electrónico</label>
                        <input class="s-input" type="email" id="edit-email" name="email">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Dirección</label>
                        <input class="s-input" id="edit-address" name="address">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Calidad</label>
                        <select class="s-input" id="edit-quality" name="rating_quality">
                            <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                            <option value="4">⭐⭐⭐⭐ Bueno</option>
                            <option value="3">⭐⭐⭐ Regular</option>
                            <option value="2">⭐⭐ Malo</option>
                            <option value="1">⭐ Pésimo</option>
                        </select>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Tiempo Entrega</label>
                        <select class="s-input" id="edit-delivery" name="rating_delivery_time">
                            <option value="5">⭐⭐⭐⭐⭐ Muy rápido</option>
                            <option value="4">⭐⭐⭐⭐ Rápido</option>
                            <option value="3">⭐⭐⭐ Promedio</option>
                            <option value="2">⭐⭐ Lento</option>
                            <option value="1">⭐ Muy lento</option>
                        </select>
                    </div>
                </div>
                <button class="s-btn s-btn-primary" style="margin-top:20px;width:100%">
                    Actualizar Datos
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(supplier) {
    const modal = document.getElementById('edit-modal');
    const form = document.getElementById('edit-form');
    
    // Set form action dynamically
    form.action = `/seller/logistics/suppliers/${supplier.id}/update`;
    
    document.getElementById('edit-name').value = supplier.name;
    document.getElementById('edit-doc').value = supplier.document_number || '';
    document.getElementById('edit-contact').value = supplier.contact_person || '';
    document.getElementById('edit-phone').value = supplier.phone || '';
    document.getElementById('edit-email').value = supplier.email || '';
    document.getElementById('edit-address').value = supplier.address || '';
    document.getElementById('edit-quality').value = supplier.rating_quality || 4;
    document.getElementById('edit-delivery').value = supplier.rating_delivery_time || 4;

    modal.classList.add('open');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('open');
}
</script>
@endsection
