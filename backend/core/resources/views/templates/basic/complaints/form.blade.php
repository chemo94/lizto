@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="complaints-section py-120 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm rounded-4" style="border-radius: 16px; overflow: hidden;">
                        <div class="card-header bg-dark text-white p-4" style="background: linear-gradient(135deg, #1f2937, #111827);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h3 class="text-white mb-1"><i class="las la-book me-2" style="color: #22c55e;"></i> @lang('Libro de Reclamaciones')</h3>
                                    <p class="text-muted mb-0 fs-13">Conforme a lo establecido en el Código de Protección y Defensa del Consumidor de Perú (INDECOPI).</p>
                                </div>
                                <div class="bg-success text-white px-3 py-2 rounded-3 text-center" style="font-size: 11px; font-weight: 800; border-radius: 8px;">
                                    HOJA DE RECLAMACIÓN<br>DIGITAL
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 p-md-5 bg-white">
                            <form action="{{ route('complaints.submit') }}" method="POST">
                                @csrf

                                <!-- 1. TIPO DE RECLAMACIÓN -->
                                <div class="mb-5 pb-4 border-bottom">
                                    <h5 class="text-dark mb-3" style="font-weight: 700; border-left: 4px solid #22c55e; padding-left: 10px;">1. Tipo de Reclamación</h5>
                                    <div class="row gy-3">
                                        <div class="col-md-6">
                                            <div class="form-check p-3 border rounded-3 d-flex align-items-center gap-2" style="cursor: pointer; border-radius: 10px;">
                                                <input class="form-check-input ms-0 me-2" type="radio" name="claim_type" id="claim_type_1" value="1" checked required>
                                                <label class="form-check-label w-100" for="claim_type_1" style="cursor: pointer;">
                                                    <strong class="d-block text-dark">Reclamo</strong>
                                                    <span class="fs-12 text-muted">Disconformidad relacionada a los productos o servicios.</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check p-3 border rounded-3 d-flex align-items-center gap-2" style="cursor: pointer; border-radius: 10px;">
                                                <input class="form-check-input ms-0 me-2" type="radio" name="claim_type" id="claim_type_2" value="2" required>
                                                <label class="form-check-label w-100" for="claim_type_2" style="cursor: pointer;">
                                                    <strong class="d-block text-dark">Queja</strong>
                                                    <span class="fs-12 text-muted">Disconformidad no relacionada a los productos, sino al servicio al cliente o atención.</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. DATOS DEL CONSUMIDOR -->
                                <div class="mb-5 pb-4 border-bottom">
                                    <h5 class="text-dark mb-3" style="font-weight: 700; border-left: 4px solid #22c55e; padding-left: 10px;">2. Identificación del Consumidor Reclamante</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label text-dark fw-bold">Nombre Completo</label>
                                            <input type="text" name="full_name" class="form-control form--control" placeholder="Ingresa tus nombres y apellidos" value="{{ old('full_name') }}" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Tipo de Documento</label>
                                            <select name="document_type" class="form-select form--control" style="height: 50px;" required>
                                                <option value="DNI">DNI (Documento Nacional de Identidad)</option>
                                                <option value="CE">Carnet de Extranjería (CE)</option>
                                                <option value="RUC">RUC</option>
                                                <option value="Pasaporte">Pasaporte</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Número de Documento</label>
                                            <input type="text" name="document_number" class="form-control form--control" placeholder="Número de doc" value="{{ old('document_number') }}" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Teléfono / Celular</label>
                                            <input type="text" name="phone" class="form-control form--control" placeholder="Ej. 987654321" value="{{ old('phone') }}" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Correo Electrónico</label>
                                            <input type="email" name="email" class="form-control form--control" placeholder="nombre@ejemplo.com" value="{{ old('email') }}" required>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label text-dark fw-bold">Dirección de Domicilio (Perú)</label>
                                            <input type="text" name="address" class="form-control form--control" placeholder="Av, Calle, Nro, Dpto, Distrito, Provincia, Departamento" value="{{ old('address') }}" required>
                                        </div>

                                        <div class="col-md-12 mt-3">
                                            <div class="form-check form-switch p-0 ps-4">
                                                <input class="form-check-input" type="checkbox" name="is_minor" id="is_minor" onchange="toggleMinorFields(this)">
                                                <label class="form-check-label text-dark fw-bold ms-2" for="is_minor">Soy menor de edad</label>
                                            </div>
                                        </div>

                                        <!-- Datos Apoderado (Oculto por defecto) -->
                                        <div id="minor_fields" class="col-md-12 mt-3 p-3 bg-light rounded-3" style="display: none; border: 1.5px dashed var(--bs-border-color);">
                                            <h6 class="text-dark mb-3 fw-bold"><i class="las la-user-shield text-success me-1"></i> Datos del Padre, Madre o Representante Legal</h6>
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label class="form-label text-dark">Nombre del Apoderado</label>
                                                    <input type="text" name="guardian_name" id="guardian_name" class="form-control form--control" placeholder="Nombre completo del representante" value="{{ old('guardian_name') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark">Tipo de Documento</label>
                                                    <select name="guardian_document_type" id="guardian_document_type" class="form-select form--control" style="height: 50px;">
                                                        <option value="DNI">DNI (Documento Nacional de Identidad)</option>
                                                        <option value="CE">Carnet de Extranjería (CE)</option>
                                                        <option value="Pasaporte">Pasaporte</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark">Número de Documento</label>
                                                    <input type="text" name="guardian_document_number" id="guardian_document_number" class="form-control form--control" placeholder="Número de doc del apoderado" value="{{ old('guardian_document_number') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. IDENTIFICACIÓN DEL BIEN CONTRATADO -->
                                <div class="mb-5 pb-4 border-bottom">
                                    <h5 class="text-dark mb-3" style="font-weight: 700; border-left: 4px solid #22c55e; padding-left: 10px;">3. Detalle del Bien Contratado</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Tipo de Bien</label>
                                            <select name="item_type" class="form-select form--control" style="height: 50px;" required>
                                                <option value="1">Producto (Comidas, víveres, mercancía, etc.)</option>
                                                <option value="2">Servicio (Transporte/Taxi, Delivery, Soporte)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-dark fw-bold">Monto Reclamado (S/.)</label>
                                            <input type="number" step="0.01" min="0" name="amount_claimed" class="form-control form--control" placeholder="0.00" value="{{ old('amount_claimed', '0.00') }}" required>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label text-dark fw-bold">Descripción del Producto o Servicio Contratado</label>
                                            <textarea name="item_description" class="form-control form--control" rows="3" placeholder="Detalla qué producto compraste o qué servicio utilizaste (Ej: Nro de pedido, carrera de taxi, etc.)" required>{{ old('item_description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. DETALLE DE LA RECLAMACIÓN -->
                                <div class="mb-5">
                                    <h5 class="text-dark mb-3" style="font-weight: 700; border-left: 4px solid #22c55e; padding-left: 10px;">4. Detalle de la Reclamación y Pedido del Consumidor</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label text-dark fw-bold">Detalle de la Disconformidad (Narra los hechos)</label>
                                            <textarea name="detail" class="form-control form--control" rows="5" placeholder="Escribe aquí de forma clara y detallada qué sucedió..." required>{{ old('detail') }}</textarea>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label text-dark fw-bold">Pedido Concreto / Pretensión</label>
                                            <textarea name="request" class="form-control form--control" rows="4" placeholder="Escribe qué es lo que solicitas como solución (Ej: Devolución de dinero, cambio de producto, etc.)" required>{{ old('request') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- DECLARACIÓN Y TÉRMINOS -->
                                <div class="mb-4 p-3 bg-light rounded-3" style="border-radius: 10px;">
                                    <div class="form-check ms-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="declaration" required>
                                        <label class="form-check-label text-dark fs-13" for="declaration" style="cursor: pointer; line-height: 1.5;">
                                            Declaro bajo juramento que los datos consignados en la presente hoja de reclamación son verdaderos y se ajustan estrictamente a la realidad de los hechos.
                                        </label>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn--base-two w-100 py-3 rounded-3" style="font-size: 16px; font-weight: 700; border-radius: 10px;">
                                        <i class="las la-paper-plane me-2"></i> Registrar Reclamación / Queja
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleMinorFields(checkbox) {
            var fields = document.getElementById('minor_fields');
            var gName = document.getElementById('guardian_name');
            var gDocType = document.getElementById('guardian_document_type');
            var gDocNum = document.getElementById('guardian_document_number');
            if (checkbox.checked) {
                fields.style.display = 'block';
                gName.setAttribute('required', 'required');
                gDocType.setAttribute('required', 'required');
                gDocNum.setAttribute('required', 'required');
            } else {
                fields.style.display = 'none';
                gName.removeAttribute('required');
                gDocType.removeAttribute('required');
                gDocNum.removeAttribute('required');
            }
        }
    </script>
@endsection
