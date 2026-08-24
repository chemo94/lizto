@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-file-invoice"></i></span> Facturación Electrónica
@endsection

@section('seller-content')
<div class="s-content seller-responsive-page" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    
    <!-- SECTOR EMPRESA SELECCIONADA -->
    <div class="module-hero tax" style="margin-bottom:16px;min-height:142px;flex-wrap:wrap">
        <div>
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.85; font-weight: 800;">Empresa Activa para Facturación</span>
            @if($activeCompany)
                <h2 style="margin: 6px 0 2px 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; color: #fff;">{{ $activeCompany->business_name ?: ($seller->business_name ?? '') }}</h2>
                <div style="display: flex; gap: 12px; align-items: center; margin-top: 6px; font-size: 13px;">
                    <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 8px; font-weight: 700;">RUC: {{ $activeCompany->document_number }}</span>
                    <span style="opacity: 0.9;"><i class="las la-store"></i> {{ $activeCompany->trade_name ?: ($store->name ?? '') }}</span>
                    @if($activeCompany->address)
                        <span style="opacity: 0.8; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><i class="las la-map-marker"></i> {{ $activeCompany->address }}</span>
                    @endif
                </div>
            @else
                <h2 style="margin: 6px 0; font-size: 22px; font-weight: 800; color: #ffeb3b;"><i class="las la-exclamation-triangle"></i> Ninguna Empresa Configurada</h2>
                <p style="margin: 0; opacity: 0.9; font-size: 13px;">Registra una empresa a la derecha para empezar a emitir comprobantes electrónicos.</p>
            @endif
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="s-btn" onclick="toggleModal('modal-select-company')" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25); border-radius: 12px; font-weight: 700; height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                <i class="las la-exchange-alt"></i> Cambiar Empresa ({{ $companies->count() }})
            </button>
            <button class="s-btn" onclick="toggleModal('modal-add-company')" style="background: #fff; color: var(--s-primary); border: none; border-radius: 12px; font-weight: 800; height: 44px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <i class="las la-plus-circle"></i> Agregar Empresa
            </button>
        </div>
    </div>

    @if($activeCompany)
    <div class="s-grid-2" style="grid-template-columns: 1fr 1.2fr; gap: 24px; align-items: start; margin-bottom: 24px;">
        
        <!-- CONFIGURACION SUNAT DE LA EMPRESA SELECCIONADA -->
        <div class="s-card seller-work-card">
            <h3 style="margin-bottom: 24px; font-weight: 800; font-size: 18px; color: var(--s-text); display: flex; align-items: center; gap: 10px;">
                <i class="las la-cloud-sun" style="color: var(--s-primary); font-size: 24px; background: rgba(22, 163, 74, 0.1); padding: 8px; border-radius: 10px;"></i> Credenciales SUNAT
            </h3>

            <form method="POST" action="{{ route('seller.sunat.config') }}" enctype="multipart/form-data">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Usuario SOL *</label>
                        <input class="s-input" name="sunat_sol_user" value="{{ $activeCompany->sunat_sol_user }}" placeholder="Ej: RUC20123456789MODUSER" required style="border-radius: 10px; height: 44px;">
                    </div>

                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Clave SOL *</label>
                        <input class="s-input" type="password" name="sunat_sol_pass" value="{{ $activeCompany->sunat_sol_pass }}" placeholder="Contraseña de la clave SOL" required style="border-radius: 10px; height: 44px;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Ambiente SUNAT</label>
                            <select class="s-input" name="sunat_env" style="border-radius: 10px; height: 44px;">
                                <option value="beta" {{ $activeCompany->sunat_env === 'beta' ? 'selected' : '' }}>Pruebas (Beta)</option>
                                <option value="production" {{ $activeCompany->sunat_env === 'production' ? 'selected' : '' }}>Producción</option>
                            </select>
                        </div>

                        <div>
                            <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Contraseña Certificado</label>
                            <input class="s-input" type="password" name="sunat_cert_pass" value="{{ $activeCompany->sunat_cert_pass }}" placeholder="Contraseña del PFX" style="border-radius: 10px; height: 44px;">
                        </div>
                    </div>

                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Certificado Digital (.pfx / .p12)</label>
                        <div style="position: relative; border: 2px dashed var(--s-border); border-radius: 12px; padding: 20px; text-align: center; background: var(--s-bg-light); cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--s-primary)'" onmouseout="this.style.borderColor='var(--s-border)'">
                            <input type="file" name="sunat_cert" accept=".pfx,.p12" style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                            <i class="las la-certificate" style="font-size: 32px; color: var(--s-text-3);"></i>
                            <div style="font-size: 13px; font-weight: 700; margin-top: 6px; color: var(--s-text);">Seleccionar archivo de certificado</div>
                            <div style="font-size: 11px; color: var(--s-text-3); margin-top: 2px;">Formatos permitidos: .pfx o .p12</div>
                        </div>
                        @if($activeCompany->sunat_cert_path)
                        <div style="font-size: 12px; color: var(--s-success); font-weight: 800; margin-top: 8px; display: flex; align-items: center; gap: 6px; background: rgba(22, 163, 74, 0.05); padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(22, 163, 74, 0.1);">
                            <i class="las la-check-circle" style="font-size: 16px;"></i> Certificado digital cargado correctamente
                        </div>
                        @endif
                    </div>
                </div>

                <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; gap: 8px; border-radius: 10px; height: 44px; font-weight: 800;">
                    <i class="las la-save"></i> Guardar Credenciales SUNAT
                </button>
            </form>
        </div>

        <!-- DATOS DE LA EMPRESA SELECCIONADA -->
        <div class="s-card seller-work-card">
            <h3 style="margin-bottom: 24px; font-weight: 800; font-size: 18px; color: var(--s-text); display: flex; align-items: center; gap: 10px;">
                <i class="las la-building" style="color: var(--s-primary); font-size: 24px; background: rgba(22, 163, 74, 0.1); padding: 8px; border-radius: 10px;"></i> Modificar Datos Fiscales
            </h3>

            <form method="POST" action="{{ route('seller.companies.update', $activeCompany->id) }}">
                @csrf
                <div style="display: flex; gap: 8px; margin-bottom: 16px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Consultar RUC</label>
                        <input class="s-input" id="search-ruc" value="{{ $activeCompany->document_number }}" placeholder="Ej: 20123456789" style="border-radius: 10px; height: 44px;">
                    </div>
                    <button type="button" class="s-btn s-btn-outline" style="height: 44px; border-radius: 10px; font-weight: 700;" onclick="searchRuc()">
                        <i class="las la-search"></i> Consultar RUC
                    </button>
                </div>

                <div id="ruc-result" style="display: none; margin-bottom: 12px; font-weight: 800; font-size: 12px;"></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">N° RUC *</label>
                        <input class="s-input" name="document_number" id="ruc-number" value="{{ $activeCompany->document_number }}" readonly required style="border-radius: 10px; height: 44px; background: var(--s-bg-light);">
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Razón Social *</label>
                        <input class="s-input" name="business_name" id="ruc-name" value="{{ $activeCompany->business_name ?: ($seller->business_name ?? '') }}" required style="border-radius: 10px; height: 44px;">
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Nombre Comercial</label>
                        <input class="s-input" name="trade_name" id="ruc-trade" value="{{ $activeCompany->trade_name ?: ($store->name ?? '') }}" style="border-radius: 10px; height: 44px;">
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Ubigeo</label>
                        <input class="s-input" name="ubigeo" id="ruc-ubigeo" value="{{ $activeCompany->ubigeo ?? '150101' }}" placeholder="Ej: 150101" style="border-radius: 10px; height: 44px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Departamento *</label>
                        <select class="s-input" name="department" id="ruc-department" required style="border-radius: 10px; height: 44px;"></select>
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Provincia *</label>
                        <select class="s-input" name="province" id="ruc-province" required style="border-radius: 10px; height: 44px;"></select>
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Distrito *</label>
                        <select class="s-input" name="district" id="ruc-district" required style="border-radius: 10px; height: 44px;"></select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">
                        <i class="las la-map-pin" style="color: var(--s-primary);"></i> Dirección Fiscal (Domicilio Legal)
                    </label>
                    <input class="s-input" name="address" id="ruc-address" value="{{ $activeCompany->address }}" placeholder="Av. Principal 123, Urbanización, Distrito..." style="border-radius: 10px; height: 44px;">
                    <span style="font-size: 11px; color: var(--s-text-muted); margin-top: 4px; display: block;">Dirección registrada ante SUNAT para comprobantes de pago.</span>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">
                        <i class="las la-percentage" style="color: var(--s-primary);"></i> Régimen Tributario IGV
                    </label>
                    <select class="s-input" name="default_tax_type" style="border-radius: 10px; height: 44px;">
                        <option value="gravado"   {{ ($activeCompany->default_tax_type ?? 'gravado') === 'gravado'   ? 'selected' : '' }}>Gravado (IGV 18%)</option>
                        <option value="exonerado" {{ ($activeCompany->default_tax_type ?? 'gravado') === 'exonerado' ? 'selected' : '' }}>Exonerado (sin IGV)</option>
                        <option value="inafecto"  {{ ($activeCompany->default_tax_type ?? 'gravado') === 'inafecto'  ? 'selected' : '' }}>Inafecto (sin IGV)</option>
                    </select>
                    <span style="font-size: 11px; color: var(--s-text-muted); margin-top: 4px; display: block;">Determina si tus facturas/boletas incluyen IGV. Las Notas de Venta nunca calculan IGV.</span>
                </div>

                <div style="border-top: 1px dashed var(--s-border); margin: 20px 0; padding-top: 16px;">
                    <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: var(--s-text); display: flex; align-items: center; gap: 6px;">
                        <i class="las la-store" style="color: var(--s-primary);"></i> Ubicación del Negocio
                    </h4>
                    <p style="font-size: 11px; color: var(--s-text-muted); margin: 0 0 12px;">Dirección física del local y coordenadas para mapa y delivery.</p>

                    <div style="margin-bottom: 12px;">
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Dirección del Local</label>
                        <div style="display: flex; gap: 8px; align-items: stretch;">
                            <input class="s-input" id="business-addr-input" name="business_address" value="{{ $activeCompany->business_address }}" placeholder="Buscar dirección o escribir manualmente..." style="border-radius: 10px; height: 44px; flex: 1;">
                            <button type="button" id="btn-my-location" class="s-btn s-btn-outline" style="height: 44px; border-radius: 10px; font-weight: 700; white-space: nowrap; padding: 0 14px;" onclick="getMyLocation()">
                                <i class="las la-crosshairs"></i> Mi Ubicación
                            </button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Latitud</label>
                            <input class="s-input" id="business-lat" type="number" step="0.0000001" min="-90" max="90" name="latitude" value="{{ $activeCompany->latitude }}" placeholder="-6.4819119" style="border-radius: 10px; height: 44px;">
                        </div>
                        <div>
                            <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Longitud</label>
                            <input class="s-input" id="business-lng" type="number" step="0.0000001" min="-180" max="180" name="longitude" value="{{ $activeCompany->longitude }}" placeholder="-76.3564149" style="border-radius: 10px; height: 44px;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button type="submit" class="s-btn s-btn-primary" style="flex: 1; justify-content: center; border-radius: 10px; height: 44px; font-weight: 800;">
                        <i class="las la-check-circle"></i> Actualizar Datos
                    </button>
                    <button type="button" class="s-btn" onclick="if(confirm('¿Eliminar esta empresa y todas sus series?')) { document.getElementById('delete-company-form').submit(); }" style="background: var(--s-danger-bg); color: var(--s-danger-text); border: 1px solid rgba(220, 38, 38, 0.1); border-radius: 10px; height: 44px; font-weight: 800; padding: 0 16px;">
                        <i class="las la-trash"></i>
                    </button>
                </div>
            </form>

            <form id="delete-company-form" method="POST" action="{{ route('seller.companies.delete', $activeCompany->id) }}" style="display: none;">
                @csrf
            </form>
        </div>

    </div>

    <!-- COMPROBANTES CONFIGURADOS -->
    <div class="s-card" style="margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid var(--s-border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-weight: 800; font-size: 18px; color: var(--s-text); display: flex; align-items: center; gap: 10px;">
                <i class="las la-list-alt" style="color: var(--s-primary); font-size: 24px; background: rgba(22, 163, 74, 0.1); padding: 8px; border-radius: 10px;"></i> Series de Comprobantes de Pago
            </h3>
            <button class="s-btn s-btn-outline" onclick="toggleModal('modal-add-invoice-type')" style="border-radius: 10px; font-weight: 700; height: 38px; display: flex; align-items: center; gap: 6px;">
                <i class="las la-plus-circle"></i> Nuevo Tipo
            </button>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
            @foreach($types as $type)
            <div style="border: 1px solid var(--s-border); border-radius: 14px; padding: 20px; background: var(--s-bg-light); transition: transform 0.3s; display: flex; flex-direction: column; justify-content: space-between; {{ $type->active ? '' : 'opacity:0.5' }}">
                
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px; border-bottom: 1px dashed var(--s-border); padding-bottom: 12px;">
                        <span class="s-badge s-badge-blue" style="font-size: 11px; font-weight: 800; border-radius: 6px; padding: 4px 8px;">Cod {{ $type->code }}</span>
                        <b style="font-size: 15px; color: var(--s-text); font-weight: 800;">{{ $type->name }}</b>
                        @if(!in_array($type->code, ['01', '03', '07', '08', 'NV']))
                        <form method="POST" action="{{ route('seller.invoice.type.delete', $type->id) }}" onsubmit="return confirm('¿Eliminar tipo de comprobante {{ $type->name }} y todas sus series?')" style="margin: 0; display: inline;">
                            @csrf
                            <button type="submit" style="background: none; border: none; padding: 0 4px; color: var(--s-danger); cursor: pointer;" title="Eliminar tipo de comprobante">
                                <i class="las la-trash-alt" style="font-size: 16px;"></i>
                            </button>
                        </form>
                        @endif
                        @if($type->is_electronic)
                        <span class="s-badge s-badge-green" style="font-size: 10px; margin-left: auto; font-weight: 800; padding: 4px 8px;"><i class="las la-bolt"></i> SUNAT</span>
                        @else
                        <span class="s-badge s-badge-gray" style="font-size: 10px; margin-left: auto; font-weight: 800; padding: 4px 8px;">Interno</span>
                        @endif
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                        @forelse($type->series as $serie)
                        <div style="background: var(--s-surface); border: 1px solid var(--s-border); padding: 10px 14px; border-radius: 12px; text-align: center; min-width: 110px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 10px rgba(0,0,0,0.01); position: relative;">
                            <span style="font-size: 17px; font-weight: 900; color: var(--s-primary); letter-spacing: 1.5px;">{{ $serie->series }}</span>
                            <span style="font-size: 11px; color: var(--s-text-3); font-weight: 700; margin-top: 4px;">Sgte: {{ $serie->nextNumber() }}</span>
                            <form method="POST" action="{{ route('seller.invoice.series.delete', $serie->id) }}" style="margin-top: 8px;" onsubmit="return confirm('¿Eliminar serie {{ $serie->series }}?')">
                                @csrf
                                <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger); padding: 4px; border-radius: 6px;">
                                    <i class="las la-trash" style="font-size: 14px;"></i>
                                </button>
                            </form>
                        </div>
                        @empty
                        <div style="font-size: 12px; color: var(--s-text-3); padding: 10px; font-style: italic;">Sin series configuradas</div>
                        @endforelse
                    </div>
                </div>

                <form method="POST" action="{{ route('seller.invoice.series.store') }}" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 8px; align-items: end; background: var(--s-surface); padding: 12px; border-radius: 10px; border: 1px solid var(--s-border);">
                    @csrf
                    <input type="hidden" name="invoice_type_id" value="{{ $type->id }}">
                    <div>
                        <label class="s-label" style="font-size: 10px; text-transform: uppercase; font-weight: 800;">Serie</label>
                        <input class="s-input" name="series" placeholder="F001" style="height: 34px; padding: 4px 8px; font-size: 12px; border-radius: 6px;" required>
                    </div>
                    <div>
                        <label class="s-label" style="font-size: 10px; text-transform: uppercase; font-weight: 800;">Inicio</label>
                        <input class="s-input" type="number" name="current_number" value="1" style="height: 34px; padding: 4px 8px; font-size: 12px; border-radius: 6px;">
                    </div>
                    <div>
                        <label class="s-label" style="font-size: 10px; text-transform: uppercase; font-weight: 800;">Máx</label>
                        <input class="s-input" type="number" name="max_number" value="999999" style="height: 34px; padding: 4px 8px; font-size: 12px; border-radius: 6px;">
                    </div>
                    <button type="submit" class="s-btn s-btn-primary s-btn-xs" style="height: 34px; padding: 0 12px; border-radius: 6px;">
                        <i class="las la-plus"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    <!-- HISTORIAL SUNAT -->
    <div class="s-card" style="box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid var(--s-border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-weight: 800; font-size: 18px; color: var(--s-text); display: flex; align-items: center; gap: 10px;">
                <i class="las la-history" style="color: var(--s-primary); font-size: 24px; background: rgba(22, 163, 74, 0.1); padding: 8px; border-radius: 10px;"></i> Comprobantes Emitidos
            </h3>
        </div>

        <!-- Filtros de Búsqueda -->
        <form method="GET" action="{{ route('seller.invoicing') }}" style="background: var(--s-bg-light); border: 1px solid var(--s-border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
                <div>
                    <label class="s-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase;">Buscar</label>
                    <input class="s-input" type="text" name="search" value="{{ request('search') }}" placeholder="Serie, correlativo o cliente..." style="height: 38px; border-radius: 8px; font-size: 13px;">
                </div>
                <div>
                    <label class="s-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase;">Tipo Comprobante</label>
                    <select class="s-input" name="tipo_doc" style="height: 38px; border-radius: 8px; font-size: 13px;">
                        <option value="">Todos los tipos</option>
                        <option value="01" {{ request('tipo_doc') == '01' ? 'selected' : '' }}>Factura (01)</option>
                        <option value="03" {{ request('tipo_doc') == '03' ? 'selected' : '' }}>Boleta (03)</option>
                        <option value="07" {{ request('tipo_doc') == '07' ? 'selected' : '' }}>Nota de Crédito (07)</option>
                        <option value="08" {{ request('tipo_doc') == '08' ? 'selected' : '' }}>Nota de Débito (08)</option>
                        <option value="NV" {{ request('tipo_doc') == 'NV' ? 'selected' : '' }}>Nota de Venta (NV)</option>
                    </select>
                </div>
                <div>
                    <label class="s-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase;">Estado SUNAT</label>
                    <select class="s-input" name="cdr_status" style="height: 38px; border-radius: 8px; font-size: 13px;">
                        <option value="">Todos los estados</option>
                        <option value="accepted" {{ request('cdr_status') == 'accepted' ? 'selected' : '' }}>Aceptado (SUNAT)</option>
                        <option value="pending" {{ request('cdr_status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="rejected" {{ request('cdr_status') == 'rejected' ? 'selected' : '' }}>Rechazado</option>
                        <option value="error" {{ request('cdr_status') == 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                </div>
                <div>
                    <label class="s-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase;">Desde</label>
                    <input class="s-input" type="date" name="date_from" value="{{ request('date_from') }}" style="height: 38px; border-radius: 8px; font-size: 13px;">
                </div>
                <div>
                    <label class="s-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase;">Hasta</label>
                    <input class="s-input" type="date" name="date_to" value="{{ request('date_to') }}" style="height: 38px; border-radius: 8px; font-size: 13px;">
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="s-btn s-btn-primary" style="height: 38px; border-radius: 8px; flex: 1; justify-content: center; font-weight: 700;">
                        <i class="las la-search"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['search', 'tipo_doc', 'cdr_status', 'date_from', 'date_to']))
                    <a href="{{ route('seller.invoicing') }}" class="s-btn s-btn-outline" style="height: 38px; border-radius: 8px; padding: 0 12px;" title="Limpiar Filtros">
                        <i class="las la-sync"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
        
        @if($sunatInvoices->count())
        <div class="s-table-wrapper" style="border-radius: 12px; border: 1px solid var(--s-border); overflow-x: auto; overflow-y: hidden;">
            <table class="s-table">
                <thead>
                    <tr style="background: var(--s-bg-light);">
                        <th style="font-weight: 800; padding: 16px;">Nº Comprobante</th>
                        <th style="font-weight: 800; padding: 16px;">Cliente</th>
                        <th style="text-align: right; font-weight: 800; padding: 16px;">Total Cuenta</th>
                        <th style="text-align: center; font-weight: 800; padding: 16px;">Estado SUNAT</th>
                        <th style="font-weight: 800; padding: 16px;">Fecha de Emisión</th>
                        <th style="text-align: center; font-weight: 800; padding: 16px; width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sunatInvoices as $inv)
                    <tr style="transition: background 0.2s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background='none'">
                        <td style="padding: 16px;">
                            <div style="font-weight: 900; color: var(--s-text); font-size: 14px;">{{ $inv->serie }}-{{ str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT) }}</div>
                            <span style="font-size: 11px; font-weight: 700; color: var(--s-text-3);">{{ $inv->tipo_doc === '01' ? 'Factura Electrónica' : ($inv->tipo_doc === '03' ? 'Boleta Electrónica' : ($inv->tipo_doc === '07' ? 'Nota de Crédito' : ($inv->tipo_doc === 'NV' ? 'Nota de Venta' : 'Nota de Débito'))) }}</span>
                        </td>
                        <td style="padding: 16px;">
                            <div style="font-weight: 700; color: var(--s-text);">{{ $inv->cliente_nombre }}</div>
                            <span style="font-size: 11px; color: var(--s-text-3);">{{ $inv->cliente_num_doc }}</span>
                        </td>
                        <td style="text-align: right; font-weight: 900; color: var(--s-primary); padding: 16px; font-size: 14px;">
                            S/ {{ number_format($inv->total, 2) }}
                        </td>
                        <td style="text-align: center; padding: 16px;">
                            <span class="s-badge" style="background: {{ $inv->statusColor() }}; color: #fff; font-size: 11px; padding: 4px 10px; font-weight: 800; border-radius: 6px;">
                                {{ $inv->statusLabel() }}
                            </span>
                        </td>
                        <td style="font-size: 12px; color: var(--s-text-3); padding: 16px; font-weight: 600;">
                            {{ $inv->fecha_emision?->format('d/m/Y H:i') }}
                        </td>
                        <td style="text-align: center; padding: 12px;">
                            <div style="display:flex; gap:4px; justify-content:center;">
                                <a href="{{ route('seller.invoice.detail', $inv->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Ver detalle" style="padding:4px 8px;">
                                    <i class="las la-eye" style="font-size:16px;color:var(--s-primary);"></i>
                                </a>
                                <a href="{{ route('seller.invoice.pdf', [$inv->id, 'a4']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Previsualizar PDF" style="padding:4px 8px;">
                                    <i class="las la-file-pdf" style="font-size:16px;color:#dc2626;"></i>
                                </a>
                                <a href="{{ route('seller.invoice.xml', $inv->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar XML" style="padding:4px 8px;">
                                    <i class="las la-file-code" style="font-size:16px;color:#8b5cf6;"></i>
                                </a>
                                <a href="{{ route('seller.invoice.pdf', [$inv->id, 'ticket']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Previsualizar Ticket" style="padding:4px 8px;">
                                    <i class="las la-receipt" style="font-size:16px;color:var(--s-info);"></i>
                                </a>
                                @if(in_array($inv->cdr_status, ['pending', 'error', 'rejected']))
                                <form method="POST" action="{{ route('seller.invoice.resend', $inv->id) }}" onsubmit="return confirm('¿Reenviar a SUNAT?');" style="display:inline; margin:0;">
                                    @csrf
                                    <button type="submit" class="s-btn s-btn-ghost s-btn-xs" title="Reenviar a SUNAT" style="padding:4px 8px;">
                                        <i class="las la-redo" style="font-size:16px;color:var(--s-info);"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($sunatInvoices->hasPages())
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $sunatInvoices->links() }}
        </div>
        @endif

        @else
        <div style="text-align: center; padding: 40px 20px; background: var(--s-bg-light); border-radius: 12px; border: 1px dashed var(--s-border);">
            <i class="las la-file-invoice" style="font-size: 48px; color: var(--s-text-3); margin-bottom: 12px; display: block;"></i>
            <p style="margin: 0; color: var(--s-text-2); font-weight: 700; font-size: 14px;">No se encontraron comprobantes emitidos</p>
            <p style="margin: 4px 0 0 0; color: var(--s-text-3); font-size: 12px;">Los comprobantes que emitas desde el POS o facturación aparecerán aquí.</p>
        </div>
        @endif
    </div>
    @else
    <div style="text-align: center; padding: 80px 20px; background: var(--s-surface); border: 1px solid var(--s-border); border-radius: 16px;">
        <i class="las la-building" style="font-size: 64px; color: var(--s-text-3); margin-bottom: 20px; display: block;"></i>
        <h3 style="font-weight: 800; color: var(--s-text); font-size: 20px;">Registra tu Primera Empresa</h3>
        <p style="color: var(--s-text-3); max-width: 500px; margin: 8px auto 24px auto; font-size: 14px; line-height: 1.6;">Para poder realizar facturación electrónica y configurar comprobantes, primero debes registrar los datos fiscales de tu empresa.</p>
        <button class="s-btn s-btn-primary" onclick="toggleModal('modal-add-company')" style="border-radius: 12px; font-weight: 800; padding: 12px 24px; font-size: 14px;">
            <i class="las la-plus-circle"></i> Agregar Nueva Empresa
        </button>
    </div>
    @endif

</div>

<!-- MODAL SELECCIONAR EMPRESA -->
<div id="modal-select-company" class="seller-responsive-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999;">
    <div class="s-card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-weight: 900; font-size: 18px; color: var(--s-text);">Seleccionar Empresa</h3>
            <button class="s-btn s-btn-ghost" onclick="toggleModal('modal-select-company')" style="padding: 4px; font-size: 20px; color: var(--s-text-3);"><i class="las la-times"></i></button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr; gap: 12px; max-height: 350px; overflow-y: auto; padding-right: 4px;">
            @foreach($companies as $comp)
            <div style="border: 2px solid {{ $comp->is_active ? 'var(--s-primary)' : 'var(--s-border)' }}; border-radius: 12px; padding: 16px; background: var(--s-bg-light); display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; font-weight: 800; font-size: 15px; color: var(--s-text);">{{ $comp->business_name }}</h4>
                    <span style="font-size: 12px; font-weight: 700; color: var(--s-text-3);">RUC: {{ $comp->document_number }}</span>
                </div>
                @if($comp->is_active)
                <span class="s-badge s-badge-green" style="font-weight: 800; font-size: 11px; padding: 6px 10px; border-radius: 6px;">Activa</span>
                @else
                <form method="POST" action="{{ route('seller.companies.switch', $comp->id) }}">
                    @csrf
                    <button type="submit" class="s-btn s-btn-outline s-btn-sm" style="border-radius: 8px; font-weight: 700; font-size: 12px; height: 32px; padding: 0 12px;">Seleccionar</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
</div>
</div>

<!-- MODAL AGREGAR TIPO DE COMPROBANTE -->
<div id="modal-add-invoice-type" class="seller-responsive-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999;">
    <div class="s-card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); background: var(--s-surface);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-weight: 900; font-size: 18px; color: var(--s-text);">Nuevo Tipo de Comprobante</h3>
            <button class="s-btn s-btn-ghost" onclick="toggleModal('modal-add-invoice-type')" style="padding: 4px; font-size: 20px; color: var(--s-text-3);"><i class="las la-times"></i></button>
        </div>
        
        <form method="POST" action="{{ route('seller.invoice.type.store') }}">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Nombre del Comprobante *</label>
                    <input class="s-input" name="name" placeholder="Ej: Guía de Remisión Interna" required style="border-radius: 10px; height: 44px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Código Corto *</label>
                        <input class="s-input" name="code" placeholder="Ej: GR" required style="border-radius: 10px; height: 44px;">
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Código SUNAT</label>
                        <input class="s-input" name="sunat_code" placeholder="Ej: 09 (Opcional)" style="border-radius: 10px; height: 44px;">
                    </div>
                </div>

                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">¿Es Electrónico (SUNAT)?</label>
                    <select class="s-input" name="is_electronic" required style="border-radius: 10px; height: 44px;">
                        <option value="0">No (Documento Interno / Administrativo)</option>
                        <option value="1">Sí (Enviar a SUNAT - Requiere homologación XML)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; border-radius: 10px; height: 44px; font-weight: 800;">
                <i class="las la-plus-circle"></i> Registrar Tipo de Comprobante
            </button>
        </form>
    </div>
</div>

<!-- MODAL AGREGAR EMPRESA -->
<div id="modal-add-company" class="seller-responsive-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999;">
    <div class="s-card" style="width: 100%; max-width: 550px; padding: 24px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-weight: 900; font-size: 18px; color: var(--s-text);">Registrar Nueva Empresa</h3>
            <button class="s-btn s-btn-ghost" onclick="toggleModal('modal-add-company')" style="padding: 4px; font-size: 20px; color: var(--s-text-3);"><i class="las la-times"></i></button>
        </div>
        
        <form method="POST" action="{{ route('seller.companies.store') }}">
            @csrf
            <div style="display: flex; gap: 8px; margin-bottom: 16px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Consultar RUC</label>
                    <input class="s-input" id="new-search-ruc" placeholder="Ej: 20123456789" style="border-radius: 10px; height: 44px;">
                </div>
                <button type="button" class="s-btn s-btn-outline" style="height: 44px; border-radius: 10px; font-weight: 700;" onclick="searchRucNew()">
                    <i class="las la-search"></i> Consultar
                </button>
            </div>

            <div id="new-ruc-result" style="display: none; margin-bottom: 12px; font-weight: 800; font-size: 12px;"></div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">N° RUC *</label>
                    <input class="s-input" name="document_number" id="new-ruc-number" readonly required style="border-radius: 10px; height: 44px; background: var(--s-bg-light);">
                </div>
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Razón Social *</label>
                    <input class="s-input" name="business_name" id="new-ruc-name" required style="border-radius: 10px; height: 44px;">
                </div>
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Nombre Comercial</label>
                    <input class="s-input" name="trade_name" id="new-ruc-trade" style="border-radius: 10px; height: 44px;">
                </div>
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Ubigeo</label>
                    <input class="s-input" name="ubigeo" id="new-ruc-ubigeo" placeholder="Ej: 150101" style="border-radius: 10px; height: 44px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Departamento *</label>
                    <select class="s-input" name="department" id="new-ruc-department" required style="border-radius: 10px; height: 44px;"></select>
                </div>
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Provincia *</label>
                    <select class="s-input" name="province" id="new-ruc-province" required style="border-radius: 10px; height: 44px;"></select>
                </div>
                <div>
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Distrito *</label>
                    <select class="s-input" name="district" id="new-ruc-district" required style="border-radius: 10px; height: 44px;"></select>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="s-label" style="font-weight: 700; font-size: 12px;">
                    <i class="las la-map-pin" style="color: var(--s-primary);"></i> Dirección Fiscal
                </label>
                <input class="s-input" name="address" id="new-ruc-address" placeholder="Av. Principal 123, Distrito..." style="border-radius: 10px; height: 44px;">
                <span style="font-size: 11px; color: var(--s-text-muted); margin-top: 3px; display: block;">Registrada ante SUNAT.</span>
            </div>

            <div style="border-top: 1px dashed var(--s-border); margin: 16px 0; padding-top: 14px;">
                <h4 style="margin: 0 0 8px; font-size: 12px; font-weight: 800; color: var(--s-text);">
                    <i class="las la-store" style="color: var(--s-primary);"></i> Ubicación del Negocio
                </h4>
                <div style="margin-bottom: 12px;">
                    <label class="s-label" style="font-weight: 700; font-size: 12px;">Dirección del Local</label>
                    <div style="display: flex; gap: 8px; align-items: stretch;">
                        <input class="s-input" id="new-business-addr-input" name="business_address" placeholder="Buscar dirección o escribir manualmente..." style="border-radius: 10px; height: 44px; flex: 1;">
                        <button type="button" class="s-btn s-btn-outline" style="height: 44px; border-radius: 10px; font-weight: 700; white-space: nowrap; padding: 0 14px;" onclick="getMyLocationNew()">
                            <i class="las la-crosshairs"></i> GPS
                        </button>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px;">Latitud</label>
                        <input class="s-input" id="new-business-lat" type="number" step="0.0000001" min="-90" max="90" name="latitude" placeholder="-6.4819119" style="border-radius: 10px; height: 44px;">
                    </div>
                    <div>
                        <label class="s-label" style="font-weight: 700; font-size: 12px;">Longitud</label>
                        <input class="s-input" id="new-business-lng" type="number" step="0.0000001" min="-180" max="180" name="longitude" placeholder="-76.3564149" style="border-radius: 10px; height: 44px;">
                    </div>
                </div>
            </div>

            <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; border-radius: 10px; height: 44px; font-weight: 800;">
                <i class="las la-plus-circle"></i> Registrar e Iniciar Empresa
            </button>
        </form>
    </div>
</div>

@push('script')
<script src="{{ asset('assets/js/ubigeos.js') }}?v={{ time() }}"></script>
<script>
function toggleModal(id) {
    var modal = document.getElementById(id);
    if(modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}

function searchRuc() {
    var num = document.getElementById('search-ruc').value.trim();
    if(!num || num.length < 11){ alert('Ingresa un RUC válido (11 dígitos)'); return; }
    var r = document.getElementById('ruc-result');
    r.style.display = 'block';
    r.innerHTML = '<span style="color:var(--s-info)"><i class="las la-spinner la-spin"></i> Consultando SUNAT...</span>';
    
    fetch('/seller/sunat-lookup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({numdoc: num, tpdoc: '6'})
    })
    .then(x => x.json())
    .then(d => {
        if(d.status && d.nombre){
            document.getElementById('ruc-number').value = d.numeroDocumento || num;
            document.getElementById('ruc-name').value = d.nombre;
            document.getElementById('ruc-trade').value = d.nombreComercial || '';
            document.getElementById('ruc-address').value = d.direccion || '';
            r.innerHTML = '<span style="color:var(--s-success)">✓ ' + d.nombre + '</span>';
        } else {
            r.innerHTML = '<span style="color:var(--s-danger)">' + (d.result || 'No encontrado') + '. Ingresa los datos manualmente.</span>';
        }
    })
    .catch(function(){ 
        r.innerHTML = '<span style="color:var(--s-danger)">Error de conexión con el servicio</span>'; 
    });
}

function searchRucNew() {
    var num = document.getElementById('new-search-ruc').value.trim();
    if(!num || num.length < 11){ alert('Ingresa un RUC válido (11 dígitos)'); return; }
    var r = document.getElementById('new-ruc-result');
    r.style.display = 'block';
    r.innerHTML = '<span style="color:var(--s-info)"><i class="las la-spinner la-spin"></i> Consultando SUNAT...</span>';
    
    fetch('/seller/sunat-lookup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({numdoc: num, tpdoc: '6'})
    })
    .then(x => x.json())
    .then(d => {
        if(d.status && d.nombre){
            document.getElementById('new-ruc-number').value = d.numeroDocumento || num;
            document.getElementById('new-ruc-name').value = d.nombre;
            document.getElementById('new-ruc-trade').value = d.nombreComercial || '';
            document.getElementById('new-ruc-address').value = d.direccion || '';
            r.innerHTML = '<span style="color:var(--s-success)">✓ ' + d.nombre + '</span>';
        } else {
            r.innerHTML = '<span style="color:var(--s-danger)">' + (d.result || 'No encontrado') + '. Ingresa los datos manualmente.</span>';
        }
    })
    .catch(function(){ 
        r.innerHTML = '<span style="color:var(--s-danger)">Error de conexión con el servicio</span>'; 
    });
}

function getMyLocation() {
    var btn = document.getElementById('btn-my-location');
    if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización.'); return; }
    btn.innerHTML = '<i class="las la-spinner la-spin"></i> Obteniendo...';
    btn.disabled = true;
    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        document.getElementById('business-lat').value = lat.toFixed(7);
        document.getElementById('business-lng').value = lng.toFixed(7);
        reverseGeocode(lat, lng, function(addr) {
            if (addr) document.getElementById('business-addr-input').value = addr;
            btn.innerHTML = '<i class="las la-crosshairs"></i> Mi Ubicación';
            btn.disabled = false;
        });
    }, function(err) {
        alert('No se pudo obtener tu ubicación. Activa el GPS o ingresala manualmente.');
        btn.innerHTML = '<i class="las la-crosshairs"></i> Mi Ubicación';
        btn.disabled = false;
    }, { enableHighAccuracy: true, timeout: 10000 });
}

function getMyLocationNew() {
    if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        document.getElementById('new-business-lat').value = lat.toFixed(7);
        document.getElementById('new-business-lng').value = lng.toFixed(7);
        reverseGeocode(lat, lng, function(addr) {
            if (addr) document.getElementById('new-business-addr-input').value = addr;
        });
    }, function(err) {
        alert('No se pudo obtener tu ubicación. Activa el GPS o ingresala manualmente.');
    }, { enableHighAccuracy: true, timeout: 10000 });
}

function reverseGeocode(lat, lng, callback) {
    if (!window.google || !google.maps) { callback(null); return; }
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
        if (status === 'OK' && results[0]) {
            callback(results[0].formatted_address);
        } else {
            callback(null);
        }
    });
}

function initInvoicingAutocomplete() {
    if (!window.google || !google.maps || !google.maps.places) return;

    var addrInput = document.getElementById('business-addr-input');
    var latInput  = document.getElementById('business-lat');
    var lngInput  = document.getElementById('business-lng');
    if (addrInput) {
        var ac1 = new google.maps.places.Autocomplete(addrInput, { componentRestrictions: { country: 'pe' } });
        ac1.addListener('place_changed', function() {
            var place = this.getPlace();
            if (place.geometry) {
                latInput.value = place.geometry.location.lat().toFixed(7);
                lngInput.value = place.geometry.location.lng().toFixed(7);
            }
        });
    }

    var newAddrInput = document.getElementById('new-business-addr-input');
    var newLatInput  = document.getElementById('new-business-lat');
    var newLngInput  = document.getElementById('new-business-lng');
    if (newAddrInput) {
        var ac2 = new google.maps.places.Autocomplete(newAddrInput, { componentRestrictions: { country: 'pe' } });
        ac2.addListener('place_changed', function() {
            var place = this.getPlace();
            if (place.geometry) {
                newLatInput.value = place.geometry.location.lat().toFixed(7);
                newLngInput.value = place.geometry.location.lng().toFixed(7);
            }
        });
    }
}

function setupUbigeoSelectors(depId, provId, distId, ubigeoInputId, selectedDep, selectedProv, selectedDist) {
    var depSelect = document.getElementById(depId);
    var provSelect = document.getElementById(provId);
    var distSelect = document.getElementById(distId);
    
    if (!depSelect || !provSelect || !distSelect) return;
    if (typeof PERU_UBIGEOS === 'undefined') {
        console.error('PERU_UBIGEOS data is not loaded.');
        return;
    }
    
    // Populate departments
    depSelect.innerHTML = '';
    Object.keys(PERU_UBIGEOS).forEach(function(dep) {
        var opt = document.createElement('option');
        opt.value = dep;
        opt.textContent = dep;
        if (selectedDep && dep === selectedDep.toUpperCase()) {
            opt.selected = true;
        }
        depSelect.appendChild(opt);
    });
    
    function updateProvinces() {
        var dep = depSelect.value;
        provSelect.innerHTML = '';
        if (dep && PERU_UBIGEOS[dep]) {
            Object.keys(PERU_UBIGEOS[dep]).forEach(function(prov) {
                var opt = document.createElement('option');
                opt.value = prov;
                opt.textContent = prov;
                if (selectedProv && prov === selectedProv.toUpperCase()) {
                    opt.selected = true;
                }
                provSelect.appendChild(opt);
            });
        }
        updateDistricts();
    }
    
    function updateDistricts() {
        var dep = depSelect.value;
        var prov = provSelect.value;
        distSelect.innerHTML = '';
        if (dep && prov && PERU_UBIGEOS[dep] && PERU_UBIGEOS[dep][prov]) {
            var data = PERU_UBIGEOS[dep][prov];
            if (Array.isArray(data)) {
                data.forEach(function(dist) {
                    var opt = document.createElement('option');
                    opt.value = dist;
                    opt.textContent = dist;
                    if (selectedDist && dist === selectedDist.toUpperCase()) {
                        opt.selected = true;
                    }
                    distSelect.appendChild(opt);
                });
            } else {
                Object.keys(data).forEach(function(dist) {
                    var opt = document.createElement('option');
                    opt.value = dist;
                    opt.textContent = dist;
                    if (selectedDist && dist === selectedDist.toUpperCase()) {
                        opt.selected = true;
                    }
                    distSelect.appendChild(opt);
                });
            }
        }
        updateUbigeoValue();
    }

    function updateUbigeoValue() {
        var dep = depSelect.value;
        var prov = provSelect.value;
        var dist = distSelect.value;
        var uInput = document.getElementById(ubigeoInputId);
        if (uInput && dep && prov && dist && PERU_UBIGEOS[dep] && PERU_UBIGEOS[dep][prov]) {
            var data = PERU_UBIGEOS[dep][prov];
            if (!Array.isArray(data) && data[dist]) {
                uInput.value = data[dist];
            }
        }
    }
    
    depSelect.addEventListener('change', function() {
        selectedProv = null;
        selectedDist = null;
        updateProvinces();
    });
    provSelect.addEventListener('change', function() {
        selectedDist = null;
        updateDistricts();
    });
    distSelect.addEventListener('change', updateUbigeoValue);
    
    updateProvinces();
}

document.addEventListener('DOMContentLoaded', function() {
    @if($activeCompany)
    setupUbigeoSelectors(
        'ruc-department', 
        'ruc-province', 
        'ruc-district', 
        'ruc-ubigeo',
        '{{ $activeCompany->department ?? "LIMA" }}', 
        '{{ $activeCompany->province ?? "LIMA" }}', 
        '{{ $activeCompany->district ?? "LIMA" }}'
    );
    @endif
    
    setupUbigeoSelectors(
        'new-ruc-department', 
        'new-ruc-province', 
        'new-ruc-district', 
        'new-ruc-ubigeo',
        'LIMA', 
        'LIMA', 
        'LIMA'
    );
});
</script>

@if(gs('google_maps_api'))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initInvoicingAutocomplete"></script>
@endif
@endpush
@endsection
