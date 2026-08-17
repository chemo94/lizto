@extends($activeTemplate . 'layouts.frontend')

@section('content')
<main class="biz-page">
    <!-- HERO SECTION -->
    <section class="biz-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-xl-6 text-center text-xl-start">
                    <span class="biz-kicker"><span class="badge-dot"></span> <i class="las la-store-alt"></i> Lizto para Negocios</span>
                    <h1>Software de administración todo en uno para tu restaurante</h1>
                    <p>La tecnología B2B ideal para optimizar tu rentabilidad. Controla tus compras, automatiza inventarios por recetas, gestiona comandas y mantén tu caja cuadrada en todo momento. Apto para locales independientes, cadenas y franquicias.</p>
                    <div class="biz-actions justify-content-center justify-content-xl-start">
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20una%20demo%20de%20Lizto%20Empresas" target="_blank" class="biz-btn biz-btn--primary">
                            <i class="lab la-whatsapp"></i>
                            <span>Agendar Demo de Ventas</span>
                        </a>
                        <a href="{{ route('seller.login') }}" class="biz-btn biz-btn--outline">
                            <i class="las la-sign-in-alt"></i>
                            <span>Acceder al Panel</span>
                        </a>
                    </div>
                    <div class="biz-metrics">
                        <div><strong>100%</strong><span>Nube en Tiempo Real</span></div>
                        <div><strong>SUNAT</strong><span>Facturación Integrada</span></div>
                        <div><strong>+40%</strong><span>Eficiencia Operativa</span></div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="biz-console-wrapper">
                        <div class="biz-console-glow"></div>
                        <div class="biz-console">
                            <div class="console-header">
                                <div>
                                    <small>MÓDULO GERENCIAL</small>
                                    <strong>Estadísticas de la Red</strong>
                                </div>
                                <span class="badge-live">En Línea</span>
                            </div>
                            <div class="console-stats">
                                <div><small>Caja Total</small><strong>S/ 12,480</strong><em class="text-success"><i class="las la-angle-up"></i> +12%</em></div>
                                <div><small>Insumos Alerta</small><strong class="text-danger">3 items</strong><em class="text-danger">Reordenar</em></div>
                                <div><small>Plato Estrella</small><strong>Ceviche Mix</strong><em>48 vendidos</em></div>
                            </div>
                            <div class="console-board">
                                <div class="ticket">
                                    <span>Almacén Central</span>
                                    <strong>Recepción de Proveedor #120</strong>
                                    <p>Ingreso de 50kg Pescado Fresco. Kardex actualizado.</p>
                                    <b class="badge-status-ok">Aprobado</b>
                                </div>
                                <div class="ticket">
                                    <span>Caja Salon 1</span>
                                    <strong>Arqueo Parcial - Turno Tarde</strong>
                                    <p>Efectivo: S/ 3,450 | Tarjetas: S/ 4,120. Sin diferencias.</p>
                                    <b class="badge-status-ok">Cuadrado</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HORIZONTAL LOGO BAR -->
    <section class="biz-band">
        <div class="container">
            <div class="biz-band-grid">
                <span><i class="las la-cash-register"></i> Punto de Venta</span>
                <span><i class="las la-fire"></i> Pantalla Cocina</span>
                <span><i class="las la-warehouse"></i> Control Kardex</span>
                <span><i class="las la-users"></i> Asistencia Mozos</span>
                <span><i class="las la-file-invoice"></i> Boleta/Factura</span>
                <span><i class="las la-chart-bar"></i> Reportes BI</span>
            </div>
        </div>
    </section>

    <!-- FEATURE GRID -->
    <section class="biz-section">
        <div class="container">
            <div class="biz-heading">
                <span class="biz-section-tag">SOLUCIONES ENTERPRISE</span>
                <h2>Módulos modulares que crecen junto con tu restaurante</h2>
                <p>Configura el sistema según las necesidades del día a día de tu negocio y agrega herramientas avanzadas cuando lo requieras.</p>
            </div>
            <div class="biz-modules">
                @php
                    $features = [
                        ['la-shopping-bag', 'Ventas y App Web', 'Recibe pedidos desde una carta digital optimizada para teléfonos móviles, ideal para delivery y recojo sin costos de comisiones.'],
                        ['la-cash-register', 'Control de POS', 'Realiza comandas a mesa, impresión térmica a cocina, división de cuentas y arqueos de caja rápidos y sin errores.'],
                        ['la-concierge-bell', 'Gestión de Salón', 'Plano interactivo de mesas con estados de consumo. Asignación automática de mozos y control de tiempos de atención.'],
                        ['la-fire', 'Pantalla de Cocina', 'Monitores de preparación (KDS) que ordenan las comandas automáticamente, alertando al cocinero por tiempos de retardo.'],
                        ['la-motorcycle', 'Envíos y Tracking', 'Calculadora de tarifas de delivery por zonas de cobertura e integración con mapas GPS para el seguimiento del motorizado.'],
                        ['la-boxes', 'Inventario y Recetas', 'Recetario estructurado para descontar el inventario de insumos automáticamente en base a las ventas. Evita fugas y mermas.'],
                        ['la-file-invoice-dollar', 'Facturación SUNAT', 'Envío directo de comprobantes de pago a SUNAT. Generación automática de reportes de ventas XML y PDFs listos para contabilidad.'],
                        ['la-users-cog', 'Gestión de Personal', 'Permisos jerarquizados para cajeros, mozos y administradores. Control de asistencia diaria y rendimiento de propinas.'],
                    ];
                @endphp
                @foreach($features as $feature)
                    <article class="biz-module">
                        <div class="biz-icon-box">
                            <i class="las {{ $feature[0] }}"></i>
                        </div>
                        <h3>{{ $feature[1] }}</h3>
                        <p>{{ $feature[2] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- OPERATIONS WORKFLOW -->
    <section class="biz-section biz-workflow">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <div class="biz-heading biz-heading--left">
                        <span class="biz-section-tag">LOGÍSTICA INTEGRADA</span>
                        <h2>El control total de tus costos, de compras a mesa</h2>
                        <p>Lizto te permite ingresar facturas de proveedores, recalcular el costo promedio de tus ingredientes y alertar cuando el margen neto de tus platos estrella se vea comprometido.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="workflow-list">
                        <div>
                            <div class="workflow-step-num">01</div>
                            <strong>Proveedores y Compras</strong>
                            <p>Registra las compras de materia prima, actualiza el precio promedio de insumos y gestiona cuentas por pagar.</p>
                        </div>
                        <div>
                            <div class="workflow-step-num">02</div>
                            <strong>Movimiento de Kardex</strong>
                            <p>Monitorea transferencias de inventario entre almacén central y barras, controlando mermas justificadas.</p>
                        </div>
                        <div>
                            <div class="workflow-step-num">03</div>
                            <strong>Preparación y Descuento</strong>
                            <p>Al venderse un producto en caja, se descuentan gramos exactos del stock de insumos configurados.</p>
                        </div>
                        <div>
                            <div class="workflow-step-num">04</div>
                            <strong>Costeo y Utilidades</strong>
                            <p>Visualiza gráficos en vivo sobre el costo real de tus platos vs el precio de venta sugerido al público.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING PLANS SECTION STYLE RESTAURANT.PE -->
    <section class="biz-section" id="precios">
        <div class="container">
            <div class="biz-heading">
                <span class="biz-section-tag">NUESTROS PLANES</span>
                <h2>Tarifas transparentes adaptadas al tamaño de tu negocio</h2>
                <p>Ahorra hasta un 25% con la contratación de la facturación anual. Todos nuestros planes incluyen actualizaciones de software automáticas.</p>
            </div>
            <div class="biz-plans">
                @forelse($packages ?? [] as $pkg)
                    @php
                        $isFeatured = $pkg->type === 'featured';
                        $planTag = 'PLAN ' . strtoupper($pkg->name);
                        $features = $pkg->displayFeatures();
                        if (empty($features)) {
                            if ($pkg->type === 'basic') {
                                $features = [
                                    '1 Licencia POS Salón/Caja',
                                    'Menú QR Digital Estándar',
                                    'Gestión de Clientes y Caja',
                                    'Soporte vía Email y Tickets'
                                ];
                            } elseif ($pkg->type === 'featured') {
                                $features = [
                                    'Todo lo del Plan Básico',
                                    'Recetario e Insumos Avanzados',
                                    'Reporte de Kardex y Compras',
                                    'Pantalla de Cocina (KDS)',
                                    'Soporte por WhatsApp Prioritario'
                                ];
                            } else {
                                $features = [
                                    'Todo lo del Plan Regular',
                                    'Facturación Electrónica SUNAT Ilimitada',
                                    'Módulo de Logística Multi-Almacén',
                                    'Conexión de Reportes BI Avanzados',
                                    'Ejecutivo de Cuenta Dedicado'
                                ];
                            }
                        }
                    @endphp
                    <article class="biz-plan {{ $isFeatured ? 'biz-plan--featured' : '' }}">
                        @if($isFeatured)
                            <span class="plan-tag-featured">MÁS POPULAR</span>
                        @endif
                        <span class="plan-tag">{{ $pkg->icon ? $pkg->icon . ' ' : '' }}{{ $planTag }}</span>
                        <h3>S/ {{ number_format($pkg->price, 2) }} <small>/ {{ $pkg->duration_days }} días</small></h3>
                        <p>{{ $pkg->description ?? 'Plan adaptado a las necesidades de tu negocio gastronómico.' }}</p>
                        <ul class="plan-features">
                            @foreach($features as $f)
                                <li><i class="las la-check"></i> {{ $f }}</li>
                            @endforeach
                        </ul>
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20me%20interesa%20el%20Plan%20{{ urlencode($pkg->name) }}%20de%20Lizto" target="_blank" class="plan-action-btn {{ $isFeatured ? 'btn-featured' : '' }}">Adquirir {{ $pkg->name }}</a>
                    </article>
                @empty
                    <article class="biz-plan">
                        <span class="plan-tag">PLAN BÁSICO</span>
                        <h3>S/ 390 <small>/ mes</small></h3>
                        <p>Recomendado para locales pequeños que buscan un control de comandas y POS ágil.</p>
                        <ul class="plan-features">
                            <li><i class="las la-check"></i> 1 Licencia POS Salón/Caja</li>
                            <li><i class="las la-check"></i> Menú QR Digital Estándar</li>
                            <li><i class="las la-check"></i> Gestión de Clientes y Caja</li>
                            <li><i class="las la-check"></i> Soporte vía Email y Tickets</li>
                        </ul>
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20me%20interesa%20el%20Plan%20Basico%20de%20Lizto" target="_blank" class="plan-action-btn">Adquirir Básico</a>
                    </article>
                    <article class="biz-plan biz-plan--featured">
                        <span class="plan-tag-featured">MÁS POPULAR</span>
                        <span class="plan-tag">PLAN REGULAR</span>
                        <h3>S/ 490 <small>/ mes</small></h3>
                        <p>El plan ideal para restaurantes y cevicherías que gestionan inventarios por recetas.</p>
                        <ul class="plan-features">
                            <li><i class="las la-check"></i> Todo lo del Plan Básico</li>
                            <li><i class="las la-check"></i> Recetario e Insumos Avanzados</li>
                            <li><i class="las la-check"></i> Reporte de Kardex y Compras</li>
                            <li><i class="las la-check"></i> Pantalla de Cocina (KDS)</li>
                            <li><i class="las la-check"></i> Soporte por WhatsApp Prioritario</li>
                        </ul>
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20me%20interesa%20el%20Plan%20Regular%20de%20Lizto" target="_blank" class="plan-action-btn btn-featured">Adquirir Regular</a>
                    </article>
                    <article class="biz-plan">
                        <span class="plan-tag">PLAN PRO</span>
                        <h3>S/ 650 <small>/ mes</small></h3>
                        <p>Ideal para franquicias, cadenas y negocios de comida con múltiples sucursales.</p>
                        <ul class="plan-features">
                            <li><i class="las la-check"></i> Todo lo del Plan Regular</li>
                            <li><i class="las la-check"></i> Facturación Electrónica SUNAT Ilimitada</li>
                            <li><i class="las la-check"></i> Módulo de Logística Multi-Almacén</li>
                            <li><i class="las la-check"></i> Conexión de Reportes BI Avanzados</li>
                            <li><i class="las la-check"></i> Ejecutivo de Cuenta Dedicado</li>
                        </ul>
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20me%20interesa%20el%20Plan%20Pro%20de%20Lizto" target="_blank" class="plan-action-btn">Adquirir Pro</a>
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    <!-- FINAL COMMERCIAL ACTION -->
    <section class="biz-section biz-final">
        <div class="container">
            <div class="biz-final-panel">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8 text-center text-lg-start">
                        <span class="final-tag">¿TIENES DUDAS?</span>
                        <h2>Hablemos y diseñemos la solución exacta para tu restaurante</h2>
                        <p class="final-desc">Nuestros especialistas en gestión gastronómica te guiarán con una demostración adaptada al flujo de tu local sin compromisos.</p>
                    </div>
                    <div class="col-lg-4 text-center text-lg-end">
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20una%20demostracion%20de%20Lizto%20para%20mi%20negocio" target="_blank" class="final-btn-call">
                            <i class="lab la-whatsapp"></i> Contactar Especialista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('style')
<style>
    /* PLANES DE PRECIOS Y ESTILOS ESPECÍFICOS B2B */
    :root {
        --biz-primary: #10b981;
        --biz-primary-hover: #059669;
        --biz-dark: #0f172a;
        --biz-slate: #475467;
        --biz-bg-light: #f8fafc;
        --biz-border: #e2e8f0;
        --biz-shadow: 0 10px 30px -10px rgba(16, 185, 129, 0.08);
    }

    .biz-page {
        font-family: 'Inter', sans-serif;
        color: var(--biz-dark);
        background: #ffffff;
        overflow-x: hidden;
    }

    .biz-page h1,
    .biz-page h2,
    .biz-page h3,
    .biz-page h4 {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        color: var(--biz-dark);
    }

    .biz-hero {
        position: relative;
        padding: 130px 0 80px;
        background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
        overflow: hidden;
    }

    .biz-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        border-radius: 9999px;
        color: var(--biz-primary-hover);
        font-size: 13.5px;
        font-weight: 700;
        margin-bottom: 24px;
    }

    .biz-hero h1 {
        font-size: clamp(38px, 4.5vw, 56px);
        margin-bottom: 20px;
        letter-spacing: -1px;
    }

    .biz-hero p {
        font-size: 17.5px;
        line-height: 1.65;
        color: var(--biz-slate);
        margin-bottom: 32px;
    }

    .biz-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 36px;
    }

    .biz-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 28px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none !important;
        transition: all 0.25s ease;
    }

    .biz-btn--primary {
        background: var(--biz-primary);
        color: #ffffff !important;
        box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
    }

    .biz-btn--primary:hover {
        background: var(--biz-primary-hover);
        transform: translateY(-2px);
    }

    .biz-btn--outline {
        background: #ffffff;
        color: var(--biz-dark) !important;
        border: 1.5px solid var(--biz-border);
    }

    .biz-btn--outline:hover {
        background: var(--biz-bg-light);
        border-color: var(--biz-dark);
    }

    .biz-metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 32px;
    }

    .biz-metrics div {
        background: #ffffff;
        border: 1px solid var(--biz-border);
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    }

    .biz-metrics strong {
        display: block;
        font-size: 22px;
        font-weight: 800;
        color: var(--biz-dark);
    }

    .biz-metrics span {
        font-size: 12px;
        color: var(--biz-slate);
        font-weight: 600;
        display: block;
        margin-top: 2px;
    }

    /* MOCKUP CONSOLE */
    .biz-console-wrapper {
        position: relative;
        z-index: 2;
    }

    .biz-console-glow {
        position: absolute;
        inset: -20px;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 60%);
        filter: blur(20px);
        z-index: -1;
    }

    .biz-console {
        background: #ffffff;
        border: 1px solid var(--biz-border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
    }

    .console-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1.5px solid var(--biz-bg-light);
        padding-bottom: 16px;
    }

    .console-header small {
        font-size: 11px;
        font-weight: 800;
        color: var(--biz-primary-hover);
        letter-spacing: 0.5px;
    }

    .console-header strong {
        font-size: 18px;
        display: block;
        color: var(--biz-dark);
    }

    .badge-live {
        font-size: 11px;
        font-weight: 800;
        color: var(--biz-primary-hover);
        background: #ecfdf5;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .console-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .console-stats div {
        background: var(--biz-bg-light);
        border: 1px solid var(--biz-border);
        border-radius: 12px;
        padding: 12px;
    }

    .console-stats small {
        font-size: 11px;
        font-weight: 600;
        color: var(--biz-slate);
        display: block;
    }

    .console-stats strong {
        font-size: 18px;
        font-weight: 800;
        display: block;
        margin-top: 4px;
    }

    .console-stats em {
        font-size: 11px;
        font-weight: 700;
        display: block;
        font-style: normal;
        margin-top: 2px;
    }

    .console-board {
        display: grid;
        gap: 12px;
    }

    .ticket {
        background: #ffffff;
        border: 1px solid var(--biz-border);
        border-radius: 12px;
        padding: 16px;
        position: relative;
    }

    .ticket span {
        font-size: 11px;
        font-weight: 700;
        color: var(--biz-slate);
        display: block;
    }

    .ticket strong {
        font-size: 14px;
        font-weight: 800;
        display: block;
        margin-top: 2px;
        color: var(--biz-dark);
    }

    .ticket p {
        font-size: 12px;
        color: var(--biz-slate);
        margin: 6px 0 10px;
        line-height: 1.5;
    }

    .badge-status-ok {
        font-size: 10px;
        font-weight: 800;
        color: var(--biz-primary-hover);
        background: #ecfdf5;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    /* LOGO BAND */
    .biz-band {
        background: var(--biz-bg-light);
        border-top: 1px solid var(--biz-border);
        border-bottom: 1px solid var(--biz-border);
        padding: 24px 0;
    }

    .biz-band-grid {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .biz-band-grid span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: var(--biz-slate);
    }

    .biz-band-grid i {
        color: var(--biz-primary);
        font-size: 18px;
    }

    /* SECTIONS */
    .biz-section {
        padding: 100px 0;
    }

    .biz-heading {
        text-align: center;
        max-width: 680px;
        margin: 0 auto 60px;
    }

    .biz-heading--left {
        text-align: left;
        margin-left: 0;
        margin-bottom: 0;
    }

    .biz-section-tag {
        font-size: 12px;
        font-weight: 900;
        color: var(--biz-primary-hover);
        letter-spacing: 1.5px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 12px;
    }

    .biz-heading h2 {
        font-size: 36px;
        margin-bottom: 18px;
    }

    .biz-heading p {
        font-size: 16.5px;
        color: var(--biz-slate);
        line-height: 1.6;
    }

    .biz-modules {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .biz-module {
        background: #ffffff;
        border: 1px solid var(--biz-border);
        border-radius: 16px;
        padding: 30px;
        height: 100%;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .biz-module:hover {
        transform: translateY(-5px);
        border-color: var(--biz-primary);
        box-shadow: 0 12px 30px -10px rgba(16, 185, 129, 0.12);
    }

    .biz-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: #ecfdf5;
        color: var(--biz-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 20px;
    }

    .biz-module h3 {
        font-size: 18.5px;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .biz-module p {
        font-size: 14px;
        color: var(--biz-slate);
        line-height: 1.55;
        margin: 0;
    }

    /* WORKFLOW LIST */
    .biz-workflow {
        background: var(--biz-bg-light);
    }

    .workflow-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .workflow-list div {
        background: #ffffff;
        border: 1px solid var(--biz-border);
        border-radius: 16px;
        padding: 24px;
    }

    .workflow-step-num {
        width: 32px;
        height: 32px;
        background: var(--biz-dark);
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }

    .workflow-list strong {
        display: block;
        font-size: 17px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .workflow-list p {
        font-size: 13.5px;
        color: var(--biz-slate);
        line-height: 1.5;
        margin: 0;
    }

    /* PRICING PLANS */
    .biz-plans {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .biz-plan {
        background: #ffffff;
        border: 1.5px solid var(--biz-border);
        border-radius: 20px;
        padding: 40px 30px;
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: all 0.3s ease;
    }

    .biz-plan--featured {
        border-color: var(--biz-primary);
        box-shadow: 0 20px 40px -15px rgba(16, 185, 129, 0.15);
    }

    .plan-tag {
        font-size: 12px;
        font-weight: 900;
        color: var(--biz-primary-hover);
        letter-spacing: 1px;
        display: block;
        margin-bottom: 12px;
    }

    .plan-tag-featured {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--biz-primary);
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        padding: 4px 14px;
        border-radius: 9999px;
        letter-spacing: 0.5px;
    }

    .biz-plan h3 {
        font-size: 38px;
        font-weight: 800;
        margin-bottom: 14px;
    }

    .biz-plan h3 small {
        font-size: 15px;
        color: var(--biz-slate);
        font-weight: 500;
    }

    .biz-plan p {
        font-size: 14px;
        color: var(--biz-slate);
        line-height: 1.5;
        margin-bottom: 24px;
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 32px;
        display: grid;
        gap: 12px;
    }

    .plan-features li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        color: var(--biz-dark);
    }

    .plan-features li i {
        color: var(--biz-primary);
        font-size: 16px;
        margin-top: 2px;
    }

    .plan-action-btn {
        margin-top: auto;
        display: block;
        text-align: center;
        background: var(--biz-dark);
        color: #ffffff !important;
        padding: 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14.5px;
        text-decoration: none !important;
        transition: all 0.2s;
    }

    .plan-action-btn:hover {
        background: var(--biz-slate);
    }

    .btn-featured {
        background: var(--biz-primary);
    }

    .btn-featured:hover {
        background: var(--biz-primary-hover);
    }

    /* FINAL PANEL */
    .biz-final {
        padding-top: 40px;
        padding-bottom: 100px;
    }

    .biz-final-panel {
        background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
        border-radius: 24px;
        padding: 60px;
        color: #ffffff;
    }

    .final-tag {
        font-size: 12px;
        font-weight: 800;
        color: var(--biz-primary);
        letter-spacing: 1.5px;
        display: block;
        margin-bottom: 12px;
    }

    .biz-final-panel h2 {
        color: #ffffff;
        font-size: clamp(28px, 3.5vw, 42px);
        margin-bottom: 16px;
    }

    .final-desc {
        color: rgba(255, 255, 255, 0.8);
        font-size: 16.5px;
        line-height: 1.6;
        margin-bottom: 0;
    }

    .final-btn-call {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 15px 32px;
        background: var(--biz-primary);
        color: #ffffff !important;
        font-weight: 700;
        font-size: 15px;
        border-radius: 12px;
        text-decoration: none !important;
        transition: all 0.25s ease;
    }

    .final-btn-call:hover {
        background: var(--biz-primary-hover);
        transform: translateY(-2px);
    }

    /* RESPONSIVE */
    @media(max-width: 991px) {
        .biz-modules { grid-template-columns: repeat(2, 1fr); }
        .biz-plans { grid-template-columns: 1fr; }
        .workflow-list { grid-template-columns: 1fr; }
    }

    @media(max-width: 767px) {
        .biz-hero { padding-top: 90px; text-align: center; }
        .biz-actions { justify-content: center; }
        .biz-actions .biz-btn { width: 100%; }
        .biz-metrics { grid-template-columns: 1fr; }
        .biz-modules { grid-template-columns: 1fr; }
        .biz-heading h2 { font-size: 28px; }
        .biz-final-panel { padding: 40px; }
    }
</style>
@endpush
