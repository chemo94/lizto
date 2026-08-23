@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-users-cog"></i></span> Gestión de Meseros y Personal (RR.HH)
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero hr">
        <div>
            <div class="module-crumb"><i class="las la-home"></i> Seller / RR.HH / Personal</div>
            <h2>Equipo y personal</h2>
            <p>Administra colaboradores, cargos, empresas asignadas y accesos operativos.</p>
        </div>
        <div class="module-hero-stats">
            <div><b>{{ $staff->count() }}</b><small>Colaboradores</small></div>
            <div><b>{{ $staff->where('status', 'active')->count() }}</b><small>Activos</small></div>
            <div><b>{{ $companies->count() }}</b><small>Empresas</small></div>
        </div>
    </section>
    <div class="hr-workspace" style="display: grid; grid-template-columns: 360px 1fr; gap: 14px; align-items: start;">
        
        <!-- REGISTRO / EDICION -->
        <div class="s-card seller-work-card">
            <h3 id="form-title" style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-user-plus" style="color: var(--s-primary); font-size: 20px;"></i> Registrar Personal
            </h3>
            
            <form id="staff-form" method="POST" action="{{ route('seller.pos.staff.store') }}">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label class="s-label">Empresa Asignada</label>
                        <select class="s-input" name="seller_company_id" id="staff-company" required>
                            <option value="">Seleccione Empresa...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->business_name }} ({{ $company->document_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="s-label">Nombre Completo</label>
                        <input class="s-input" name="name" id="staff-name" placeholder="Ej: Juan Pérez" required autocomplete="off">
                    </div>

                    <div>
                        <label class="s-label">Nº Documento (DNI/RUC)</label>
                        <input class="s-input" name="document_number" id="staff-doc" placeholder="DNI o RUC" required autocomplete="off">
                    </div>

                    <div>
                        <label class="s-label">Cargo / Puesto</label>
                        <select class="s-input" name="position" id="staff-position" required>
                            <option value="mesero">Mesero / Mozo</option>
                            <option value="cocinero">Cocinero / Chef</option>
                            <option value="cajero">Cajero</option>
                            <option value="barman">Barman / Bartender</option>
                            <option value="administrador">Administrador</option>
                            <option value="contabilidad">Contador / Contabilidad</option>
                            <option value="seguridad">Seguridad</option>
                            <option value="limpieza">Limpieza</option>
                        </select>
                        <small style="display:block;margin-top:5px;color:var(--s-text-muted);">Al elegir Contador se asignan automáticamente los accesos de reportes, inventario y declaraciones; puedes ajustarlos antes de guardar.</small>
                    </div>
                    
                    <div class="s-form-grid-2" style="display: grid; gap: 10px;">
                        <div>
                            <label class="s-label">Celular</label>
                            <input class="s-input" name="phone" id="staff-phone" placeholder="987654321">
                        </div>
                        <div>
                            <label class="s-label">Fecha Ingreso</label>
                            <input class="s-input" type="date" name="hire_date" id="staff-hire-date">
                        </div>
                    </div>

                    <div>
                        <label class="s-label">Correo (Para Login)</label>
                        <input class="s-input" type="email" name="email" id="staff-email" placeholder="juan@correo.com">
                    </div>

                    <div>
                        <label class="s-label">Contraseña de Acceso (Opcional)</label>
                        <input class="s-input" type="password" name="password" id="staff-password" placeholder="Mínimo 4 caracteres" autocomplete="new-password">
                    </div>
                    
                    <div class="s-form-grid-2" style="display: grid; gap: 10px;">
                        <div>
                            <label class="s-label">Tipo de Pago</label>
                            <select class="s-input" name="salary_type" id="staff-salary-type" required>
                                <option value="monthly">Mensual</option>
                                <option value="daily">Diario</option>
                                <option value="hourly">Por Hora</option>
                            </select>
                        </div>
                        <div>
                            <label class="s-label">Sueldo Base (S/)</label>
                            <input class="s-input" type="number" step="0.01" min="0" name="base_salary" id="staff-salary" value="0.00" required>
                        </div>
                    </div>

                    <div class="s-form-grid-2" style="display: grid; gap: 10px;">
                        <div>
                            <label class="s-label">Comisión (%)</label>
                            <input class="s-input" type="number" step="0.01" min="0" max="100" name="commission_rate" id="staff-commission" value="0.00" required>
                        </div>
                        <div id="status-group" style="display: none;">
                            <label class="s-label">Estado</label>
                            <select class="s-input" name="status" id="staff-status">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="s-label">Permisos de Acceso</label>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px; background: var(--s-bg-light); padding: 12px; border-radius: 4px; border: 1px solid var(--s-border);">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="pos_orders" id="perm-pos"> Toma de Pedidos (POS)
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="billing" id="perm-billing"> Caja y Cobros
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="kitchen" id="perm-kitchen"> Vista de Cocina
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="products" id="perm-products"> Productos y Categorías
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="inventory" id="perm-inventory"> Almacén e Inventario
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="hr" id="perm-hr"> Recursos Humanos (RR.HH)
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="reports" id="perm-reports"> Reportes de Ventas
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="notifications" id="perm-notifications"> Notificaciones Push
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="settings" id="perm-settings"> Configuración / Facturación / API
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--s-text-primary); cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="accounting" id="perm-accounting"> Contabilidad y SUNAT (Libros/Facturación)
                            </label>
                        </div>
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

        <!-- LISTADO -->
        <div class="s-card seller-work-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-list" style="color: var(--s-primary); font-size: 20px;"></i> Fichas de Empleados
            </h3>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                            <th style="padding: 10px 14px;">Nombre / Cargo</th>
                            <th style="padding: 10px 14px;">Documento / Contacto</th>
                            <th style="padding: 10px 14px;">Permisos de Acceso</th>
                            <th style="padding: 10px 14px;">Sueldo Base</th>
                            <th style="padding: 10px 14px;">Comisión</th>
                            <th style="padding: 10px 14px;">Estado</th>
                            <th style="padding: 10px 14px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                        <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background=''">
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 700; color: var(--s-text-primary);">{{ $member->name }}</div>
                                <div style="display: flex; gap: 4px; align-items: center; margin-top: 4px;">
                                    <span class="s-badge s-badge-blue" style="font-size: 9px; padding: 2px 6px; text-transform: uppercase; font-weight:800;">
                                        {{ $member->position }}
                                    </span>
                                </div>
                                <div style="font-size: 11px; color: var(--s-text-muted); margin-top: 4px;">
                                    Empresa: <strong>{{ $member->company?->business_name ?? 'Sin asignar' }}</strong>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; color: var(--s-text-secondary);">
                                <div>Doc: <b>{{ $member->document_number ?: '—' }}</b></div>
                                <div style="font-size: 11px; color: var(--s-text-muted); margin-top: 2px;">{{ $member->phone ?: 'Sin celular' }}</div>
                                @if($member->email)
                                    <div style="font-size: 11px; color: var(--s-text-muted);">{{ $member->email }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;">
                                @if(!empty($member->permissions))
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 180px;">
                                        @foreach($member->permissions as $p)
                                            <span class="s-badge s-badge-gray" style="font-size: 9px; padding: 1px 4px; text-transform: uppercase; font-weight: bold;">
                                                {{ match($p) {
                                                    'pos_orders' => 'POS',
                                                    'billing' => 'Caja',
                                                    'kitchen' => 'Cocina',
                                                    'products' => 'Productos',
                                                    'inventory' => 'Inventario',
                                                    'hr' => 'RR.HH',
                                                    'reports' => 'Reportes',
                                                    'accounting' => 'Contabilidad',
                                                    'settings' => 'Config.',
                                                    default => $p
                                                } }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="font-size: 11px; color: var(--s-text-muted); font-style: italic;">Sin accesos</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;">
                                <div>S/ {{ number_format($member->base_salary, 2) }}</div>
                                <span style="font-size: 10px; color: var(--s-text-muted);">
                                    @if($member->salary_type === 'monthly') Mensual @elseif($member->salary_type === 'daily') Diario @else Por Hora @endif
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700; color: var(--s-accent-dark);">
                                {{ number_format($member->commission_rate, 2) }}%
                            </td>
                            <td style="padding: 12px 14px;">
                                @if($member->status === 'active')
                                <span class="s-badge s-badge-green">Activo</span>
                                @else
                                <span class="s-badge s-badge-gray">Inactivo</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-primary);" 
                                            onclick="editStaff({{ json_encode($member) }})">
                                        <i class="las la-edit" style="font-size: 16px;"></i>
                                    </button>
                                    <form method="POST" action="{{ route('seller.pos.staff.delete', $member->id) }}" onsubmit="return confirm('¿Eliminar a {{ $member->name }}?')" style="display:inline">
                                        @csrf
                                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger);">
                                            <i class="las la-trash" style="font-size: 16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px 10px; color: var(--s-text-muted);">
                                <i class="las la-users-cog" style="font-size: 48px; display: block; margin-bottom: 12px; color: var(--s-border);"></i>
                                No hay personal registrado en planilla.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

@push('script')
<script>
function editStaff(member) {
    document.getElementById('form-title').innerHTML = '<i class="las la-user-edit" style="color:var(--s-warning); font-size: 20px;"></i> Editar Personal';
    document.getElementById('staff-form').action = "{{ route('seller.pos.staff.update', '__ID__') }}".replace('__ID__', member.id);
    
    document.getElementById('staff-company').value = member.seller_company_id || '';
    document.getElementById('staff-name').value = member.name;
    document.getElementById('staff-doc').value = member.document_number || '';
    document.getElementById('staff-position').value = member.position || 'mesero';
    document.getElementById('staff-email').value = member.email || '';
    document.getElementById('staff-password').value = '';
    document.getElementById('staff-commission').value = member.commission_rate;
    document.getElementById('staff-status').value = member.status;
    document.getElementById('staff-salary-type').value = member.salary_type || 'monthly';
    document.getElementById('staff-salary').value = member.base_salary || '0.00';
    
    // Check checkboxes based on member permissions
    const perms = member.permissions || [];
    document.getElementById('perm-pos').checked = perms.includes('pos_orders');
    document.getElementById('perm-billing').checked = perms.includes('billing');
    document.getElementById('perm-kitchen').checked = perms.includes('kitchen');
    document.getElementById('perm-products').checked = perms.includes('products');
    document.getElementById('perm-inventory').checked = perms.includes('inventory');
    document.getElementById('perm-hr').checked = perms.includes('hr');
    document.getElementById('perm-reports').checked = perms.includes('reports');
    document.getElementById('perm-notifications').checked = perms.includes('notifications');
    document.getElementById('perm-settings').checked = perms.includes('settings');
    document.getElementById('perm-accounting').checked = perms.includes('accounting');
    
    if(member.hire_date) {
        document.getElementById('staff-hire-date').value = member.hire_date.substring(0, 10);
    } else {
        document.getElementById('staff-hire-date').value = '';
    }
    
    document.getElementById('status-group').style.display = 'block';
    document.getElementById('btn-cancel').style.display = 'block';
}

function resetForm() {
    document.getElementById('form-title').innerHTML = '<i class="las la-user-plus" style="color:var(--s-primary); font-size: 20px;"></i> Registrar Personal';
    document.getElementById('staff-form').action = "{{ route('seller.pos.staff.store') }}";
    document.getElementById('staff-form').reset();
    
    document.getElementById('perm-pos').checked = false;
    document.getElementById('perm-billing').checked = false;
    document.getElementById('perm-kitchen').checked = false;
    document.getElementById('perm-products').checked = false;
    document.getElementById('perm-inventory').checked = false;
    document.getElementById('perm-hr').checked = false;
    document.getElementById('perm-reports').checked = false;
    document.getElementById('perm-notifications').checked = false;
    document.getElementById('perm-settings').checked = false;
    document.getElementById('perm-accounting').checked = false;
    
    document.getElementById('status-group').style.display = 'none';
    document.getElementById('btn-cancel').style.display = 'none';
}

document.getElementById('staff-position').addEventListener('change', function () {
    if (this.value !== 'contabilidad') return;
    // Perfil de mínimo privilegio para el rol contable: consulta y exportación,
    // sin acceso a caja, POS ni configuración.
    ['perm-reports', 'perm-inventory', 'perm-accounting'].forEach(function (id) {
        document.getElementById(id).checked = true;
    });
});
</script>
@endpush
@endsection
