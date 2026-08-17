@extends($activeTemplate . 'layouts.frontend')

@section('content')
<main class="saas-landing">

    <!-- HERO -->
    <section class="saas-hero" id="inicio">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-xl-6 text-center text-xl-start">
                    <span class="hero-badge"><i class="las la-bolt"></i> Software gastronómico #1 del Perú</span>
                    <h1 class="hero-title">Tu restaurante,<br>todo bajo<br><span class="text-accent">un sistema.</span></h1>
                    <p class="hero-lead">POS, delivery, cocina, facturación electrónica e inventario en una sola plataforma. Diseñada para restaurantes peruanos que quieren crecer.</p>
                    <div class="hero-trust">
                        <span><i class="las la-check-circle"></i> Sin tarjeta de crédito</span>
                        <span><i class="las la-check-circle"></i> Setup en 24 horas</span>
                        <span><i class="las la-check-circle"></i> Soporte en español</span>
                    </div>
                    <div class="saas-actions justify-content-center justify-content-xl-start">
                        <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20solicitar%20una%20demo%20de%20Lizto" target="_blank" class="saas-btn saas-btn--primary">
                            Agenda tu demo gratis <i class="las la-angle-right"></i>
                        </a>
                        <a href="{{ route('negocios') }}" class="saas-btn saas-btn--outline">
                            Ver planes y precios <i class="las la-angle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-xl-6 d-flex justify-content-center">
                    <div class="hero-mockup">
                        <div class="mockup-laptop">
                            <div class="mockup-screen">
                                <div class="mockup-dash-topbar"><span></span><span></span><span></span></div>
                                <div class="mockup-dash-header">
                                    <div class="mockup-dash-title">Dashboard</div>
                                    <div class="mockup-dash-badge">Online</div>
                                </div>
                                <div class="mockup-dash-grid">
                                    <div class="mockup-stat-card"><span class="mockup-stat-num">127</span><span class="mockup-stat-label">Pedidos hoy</span></div>
                                    <div class="mockup-stat-card"><span class="mockup-stat-num">S/4,850</span><span class="mockup-stat-label">Ventas del día</span></div>
                                    <div class="mockup-stat-card"><span class="mockup-stat-num">98%</span><span class="mockup-stat-label">Satisfacción</span></div>
                                </div>
                                <div class="mockup-dash-chart">
                                    <div class="mockup-bar" style="height:40%"></div>
                                    <div class="mockup-bar" style="height:65%"></div>
                                    <div class="mockup-bar" style="height:45%"></div>
                                    <div class="mockup-bar" style="height:80%"></div>
                                    <div class="mockup-bar" style="height:55%"></div>
                                    <div class="mockup-bar" style="height:70%"></div>
                                    <div class="mockup-bar" style="height:90%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mockup-phone">
                            <div class="mockup-phone-notch"></div>
                            <div class="mockup-phone-screen">
                                <div class="mockup-phone-header">LizToGo</div>
                                <div class="mockup-phone-item">🍔 Burger Classic <span>S/18</span></div>
                                <div class="mockup-phone-item">🍕 Pizza Margarita <span>S/25</span></div>
                                <div class="mockup-phone-item">🍗 Pollo a la Brasa <span>S/35</span></div>
                                <div class="mockup-phone-total">Total: S/ 45.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- METRICS -->
    <section class="saas-metrics-strip">
        <div class="container">
            <div class="metrics-grid">
                <div class="metric-item">
                    <h2><span class="counter" data-target="5000">0</span>+</h2>
                    <p>Negocios activos en el Perú</p>
                </div>
                <div class="metric-item">
                    <h2><span class="counter" data-target="10">0</span>+</h2>
                    <p>Años de trayectoria</p>
                </div>
                <div class="metric-item">
                    <h2><span class="counter" data-target="12">0</span>+</h2>
                    <p>Países con nosotros</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PRODUCT SHOWCASE -->
    <section class="saas-section" id="producto">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Conoce Lizto</span>
                <h2 class="section-title">Un sistema completo para <span class="text-accent">tu restaurante</span></h2>
                <p class="section-desc">Desde el punto de venta hasta la entrega al cliente. Todo lo que necesitas, sin complicaciones.</p>
            </div>
            <div class="product-showcase">
                <div class="showcase-item">
                    <div class="showcase-icon"><i class="las la-desktop"></i></div>
                    <h4>Punto de Venta</h4>
                    <p>Interfaz táctil rápida para tomar pedidos en salón, delivery y para llevar.</p>
                </div>
                <div class="showcase-item">
                    <div class="showcase-icon"><i class="las la-utensils"></i></div>
                    <h4>Cocina Inteligente</h4>
                    <p>Pantalla de cocina con tickets, tiempos de preparación y alertas automáticas.</p>
                </div>
                <div class="showcase-item">
                    <div class="showcase-icon"><i class="las la-motorcycle"></i></div>
                    <h4>Delivery Integrado</h4>
                    <p>Gestión completa de envíos con seguimiento en tiempo real y repartidores.</p>
                </div>
                <div class="showcase-item">
                    <div class="showcase-icon"><i class="las la-file-invoice-dollar"></i></div>
                    <h4>Facturación Electrónica</h4>
                    <p>Emisión de boletas y facturas SUNAT automática, sin errores ni demoras.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="saas-section saas-section--alt" id="herramientas">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Herramientas</span>
                <h2 class="section-title">Todo lo que tu restaurante <span class="text-accent">necesita</span></h2>
                <p class="section-desc">Módulos diseñados para las necesidades reales del restaurantero peruano.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon bg-blue"><i class="las la-cash-register"></i></div>
                    <h4>POS Avanzado</h4>
                    <p>Toma de pedidos rápida con interface táctil. Delivery, salón y para llevar en un solo click.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon bg-purple"><i class="las la-fire"></i></div>
                    <h4>Cocina en Tiempo Real</h4>
                    <p>Tickets automáticos, tiempos de preparación y priorización de pedidos urgentes.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon bg-teal"><i class="las la-boxes"></i></div>
                    <h4>Inventario y Recetas</h4>
                    <p>Control de stock por ingrediente, recetas escalables y alertas de reabastecimiento.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon bg-orange"><i class="las la-chart-line"></i></div>
                    <h4>Reportes e Informes</h4>
                    <p>Ventas, costos, márgenes y rendimiento en dashboards visuales fáciles de entender.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon bg-rose"><i class="las la-file-invoice"></i></div>
                    <h4>Facturación SUNAT</h4>
                    <p>Boletas y facturas electrónicas automáticas. Cumplimiento fiscal sin complicaciones.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon bg-indigo"><i class="las la-store"></i></div>
                    <h4>Multi-Sucursal</h4>
                    <p>Gestiona varios locales desde un solo panel. Reportes consolidados y control centralizado.</p>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('negocios') }}" class="saas-btn saas-btn--outline">
                    Ver todas las herramientas <i class="las la-angle-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="saas-section" id="como-funciona">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Simple y rápido</span>
                <h2 class="section-title">Así de fácil <span class="text-accent">empezar</span></h2>
                <p class="section-desc">En 3 pasos tu restaurante está operando con Lizto.</p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Agenda tu demo</h4>
                    <p>Háblanos de tu negocio y te mostramos cómo Lizto se adapta a tu restaurante.</p>
                </div>
                <div class="step-connector"><i class="las la-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Configuración</h4>
                    <p>Nuestro equipo configura tu menú, mesas, precios y conecta tu impresora en menos de 24h.</p>
                </div>
                <div class="step-connector"><i class="las la-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>¡A vender!</h4>
                    <p>Empieza a tomar pedidos, facturar y controlar tu restaurante desde el día 1.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SUCCESS PATH -->
    <section class="saas-section saas-section--alt" id="exito">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Resultados reales</span>
                <h2 class="section-title">Impulsa tu restaurante <span class="text-accent">hacia resultados reales</span></h2>
                <p class="section-desc">Cada herramienta fue pensada para maximizar tu rentabilidad.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="success-card">
                        <div class="success-card-img" style="background-image:url('https://images.unsplash.com/photo-1552566626-52f8b828add9?w=400&auto=format&fit=crop&q=70')"></div>
                        <div class="success-card-body">
                            <strong>Optimiza tu Salón</strong>
                            <p>Controla mesas, reservas y pedidos en tiempo real. Reduce tiempos de espera.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="success-card">
                        <div class="success-card-img" style="background-image:url('https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=400&auto=format&fit=crop&q=70')"></div>
                        <div class="success-card-body">
                            <strong>Producción Controlada</strong>
                            <p>Gestiona recetas, insumos y stock por ingrediente. Reduce mermas.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="success-card">
                        <div class="success-card-img" style="background-image:url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=400&auto=format&fit=crop&q=70')"></div>
                        <div class="success-card-body">
                            <strong>Monitoreo en Tiempo Real</strong>
                            <p>Dashboard con ventas, pedidos y rendimiento al instante.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="success-card">
                        <div class="success-card-img" style="background-image:url('https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&auto=format&fit=crop&q=70')"></div>
                        <div class="success-card-body">
                            <strong>Control Multi-Sucursal</strong>
                            <p>Gestiona varios locales desde un solo sistema con reportes consolidados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="saas-section" id="testimonios">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-4 text-center text-lg-start">
                    <span class="section-tag">Testimonios</span>
                    <h2 class="section-title">Negocios peruanos que <span class="text-accent">ya crecen con Lizto</span></h2>
                    <p class="section-desc mt-3">Dueños y administradores de restaurantes que transformaron su operación.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="testimonial-card-bubble">
                                <div class="testimonial-stars">
                                    <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i>
                                </div>
                                <p class="testimonial-text-quote">"Incorporar la tecnología de administración nos permitió crecer más rápido, mejorar la experiencia del cliente y optimizar la operación diaria."</p>
                                <div class="testimonial-author-box">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80" alt="Victor Hugo" class="author-avatar">
                                    <div>
                                        <strong>Víctor Hugo de la Cruz</strong>
                                        <span>Administrador de Pickadeli</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="testimonial-card-bubble">
                                <div class="testimonial-stars">
                                    <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i>
                                </div>
                                <p class="testimonial-text-quote">"Ha sido clave para el crecimiento de mi restaurante. Me permite controlar todo desde cualquier lugar y simplifica la operación diaria al 100%."</p>
                                <div class="testimonial-author-box">
                                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80" alt="Sara Marin" class="author-avatar">
                                    <div>
                                        <strong>Sara Marín</strong>
                                        <span>Propietaria de La Mona</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    @php
        $packages = \App\Models\BusinessPackage::active()->orderBy('price', 'asc')->get();
        $minPrice = $packages->count() > 0 ? getAmount($packages->min('price')) : '390';
    @endphp
    <section class="saas-section saas-section--alt" id="precios-home">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Precios</span>
                <h2 class="section-title">Planes para todos los tamaños, <span class="text-accent">desde S/{{ $minPrice }}</span> al mes</h2>
                <p class="section-desc">Elige el plan que se ajusta a tu negocio. Sin tarjeta de crédito.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @forelse($packages as $package)
                    @php $isPro = $loop->last && $packages->count() > 2; @endphp
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card-home {{ $isPro ? 'pricing-card--featured' : '' }} h-100 flex-column d-flex justify-content-between">
                            @if($isPro)<div class="pricing-badge-featured">Más Popular</div>@endif
                            <div>
                                <div class="pricing-header-home text-center">
                                    <h4>{{ __($package->name) }}</h4>
                                    <div class="pricing-price-box">
                                        <span class="pricing-currency">S/</span>
                                        <span class="pricing-amount">{{ getAmount($package->price) }}</span>
                                        <span class="pricing-period">/ mes</span>
                                    </div>
                                </div>
                                <div class="pricing-body-home mt-4">
                                    <p class="pricing-desc-home">{{ __($package->description) }}</p>
                                    <ul class="pricing-features-list-home">
                                        @php $features = $package->displayFeatures(); @endphp
                                        @foreach($features as $feature)
                                            <li><i class="las la-check-circle"></i> <span>{{ __($feature) }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="pricing-footer-home mt-4 text-center">
                                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20empezar%20con%20el%20plan%20{{ urlencode(__($package->name)) }}" target="_blank" class="saas-btn {{ $isPro ? 'saas-btn--primary' : 'saas-btn--outline' }} w-100 justify-content-center">
                                    Empezar ahora <i class="las la-angle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card-home h-100 flex-column d-flex justify-content-between">
                            <div>
                                <div class="pricing-header-home text-center">
                                    <h4>Plan Basic</h4>
                                    <div class="pricing-price-box">
                                        <span class="pricing-currency">S/</span>
                                        <span class="pricing-amount">390</span>
                                        <span class="pricing-period">/ mes</span>
                                    </div>
                                </div>
                                <div class="pricing-body-home mt-4">
                                    <p class="pricing-desc-home">Todo lo esencial para tu restaurante.</p>
                                    <ul class="pricing-features-list-home">
                                        <li><i class="las la-check-circle"></i> <span>POS táctil para salón, delivery y para llevar.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Cocina con tickets automáticos.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Soporte en español y setup en 24h.</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="pricing-footer-home mt-4 text-center">
                                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20empezar%20con%20el%20plan%20Basic" target="_blank" class="saas-btn saas-btn--outline w-100 justify-content-center">
                                    Empezar ahora <i class="las la-angle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card-home pricing-card--featured h-100 flex-column d-flex justify-content-between">
                            <div class="pricing-badge-featured">Más Popular</div>
                            <div>
                                <div class="pricing-header-home text-center">
                                    <h4>Plan Regular</h4>
                                    <div class="pricing-price-box">
                                        <span class="pricing-currency">S/</span>
                                        <span class="pricing-amount">490</span>
                                        <span class="pricing-period">/ mes</span>
                                    </div>
                                </div>
                                <div class="pricing-body-home mt-4">
                                    <p class="pricing-desc-home">Lleva tu gestión al siguiente nivel.</p>
                                    <ul class="pricing-features-list-home">
                                        <li><i class="las la-check-circle"></i> <span>Todo lo del Plan Basic.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Logística, recetas e inventario avanzado.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Reportes e informes detallados.</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="pricing-footer-home mt-4 text-center">
                                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20empezar%20con%20el%20plan%20Regular" target="_blank" class="saas-btn saas-btn--primary w-100 justify-content-center">
                                    Empezar ahora <i class="las la-angle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card-home h-100 flex-column d-flex justify-content-between">
                            <div>
                                <div class="pricing-header-home text-center">
                                    <h4>Plan Pro</h4>
                                    <div class="pricing-price-box">
                                        <span class="pricing-currency">S/</span>
                                        <span class="pricing-amount">650</span>
                                        <span class="pricing-period">/ mes</span>
                                    </div>
                                </div>
                                <div class="pricing-body-home mt-4">
                                    <p class="pricing-desc-home">Solución integral para múltiples locales.</p>
                                    <ul class="pricing-features-list-home">
                                        <li><i class="las la-check-circle"></i> <span>Todo lo del Plan Regular.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Producción y análisis profundo.</span></li>
                                        <li><i class="las la-check-circle"></i> <span>Monitoreo multi-sucursal en tiempo real.</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="pricing-footer-home mt-4 text-center">
                                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20empezar%20con%20el%20plan%20Pro" target="_blank" class="saas-btn saas-btn--outline w-100 justify-content-center">
                                    Empezar ahora <i class="las la-angle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="saas-section" id="faq">
        <div class="container">
            <div class="saas-heading text-center mb-5">
                <span class="section-tag">Preguntas frecuentes</span>
                <h2 class="section-title">Resolvemos tus <span class="text-accent">dudas</span></h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="faq-accordion">
                        <div class="faq-item active">
                            <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                                <span>¿Cuánto tiempo tarda la configuración?</span>
                                <i class="las la-plus"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Nuestro equipo configura tu menú, mesas, precios y conecta tu impresora en menos de 24 horas. Puedes empezar a tomar pedidos desde el día 1.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                                <span>¿Necesito tarjeta de crédito para empezar?</span>
                                <i class="las la-plus"></i>
                            </button>
                            <div class="faq-answer">
                                <p>No. Puedes empezar con un demo gratuito sin tarjeta de crédito. Cancela cuando quieras.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                                <span>¿El sistema incluye facturación electrónica SUNAT?</span>
                                <i class="las la-plus"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Sí. Emisión de boletas y facturas electrónicas SUNAT automática, sin errores ni demoras.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                                <span>¿Puedo gestionar múltiples sucursales?</span>
                                <i class="las la-plus"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Sí. Gestiona varios locales desde un solo panel con reportes consolidados y control centralizado.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                                <span>¿Qué tipo de soporte ofrecen?</span>
                                <i class="las la-plus"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Soporte en español, capacitación exclusiva, implementación gratuita y tu propio ejecutivo de cuenta.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA FINAL -->
    <section class="saas-cta-final">
        <div class="container text-center">
            <h2>Tu restaurante merece un sistema que crezca contigo</h2>
            <p>Cientos de restaurantes en el Perú ya optimizaron su gestión con Lizto. Es tu turno.</p>
            <div class="saas-actions justify-content-center">
                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20solicitar%20una%20demo%20de%20Lizto" target="_blank" class="saas-btn saas-btn--white">
                    Agenda tu demo gratis <i class="las la-angle-right"></i>
                </a>
                <a href="{{ route('negocios') }}#precios" class="saas-btn saas-btn--outline-white">
                    Ver planes y precios <i class="las la-angle-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- STICKY MOBILE CTA -->
    <div class="sticky-mobile-cta" id="stickyCta">
        <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20una%20demo" target="_blank" class="saas-btn saas-btn--primary w-100 justify-content-center">
            <i class="las la-comment-dots"></i> Agenda tu demo gratis
        </a>
    </div>

</main>
@endsection

@push('style')
<style>
/* ══════════════════════════════════════
   DESIGN SYSTEM v2 — Audit-applied
   ══════════════════════════════════════ */
:root {
    --lz-primary: #16a34a;
    --lz-primary-dark: #15803d;
    --lz-primary-light: #dcfce7;
    --lz-primary-5: rgba(22,163,74,0.05);
    --lz-text-85: #0f172a;
    --lz-text-50: #475569;
    --lz-text-25: #94a3b8;
    --lz-bg: #f8fafc;
    --lz-border: #e2e8f0;
    --lz-white: #ffffff;
    --lz-dark: #0f172a;
    --lz-radius: 10px;
    --lz-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --lz-shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --lz-shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
}

html { scroll-behavior: smooth; }

.saas-landing {
    font-family: 'Montserrat', sans-serif;
    color: var(--lz-text-85);
    background: var(--lz-white);
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

.saas-landing h1, .saas-landing h2, .saas-landing h3, .saas-landing h4, .saas-landing strong {
    font-family: 'Montserrat', sans-serif;
    color: var(--lz-text-85);
}

.text-accent { color: var(--lz-primary) !important; }

/* ── BUTTONS ── */
.saas-actions { display: flex; gap: 16px; flex-wrap: wrap; }

.saas-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: var(--lz-radius);
    font-size: 15px;
    font-weight: 700;
    text-decoration: none !important;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    white-space: nowrap;
    font-family: 'Montserrat', sans-serif;
}

.saas-btn i { font-size: 14px; }

.saas-btn--primary {
    background: var(--lz-primary);
    color: var(--lz-white) !important;
    box-shadow: 0 4px 14px rgba(22,163,74,0.25);
}
.saas-btn--primary:hover {
    background: var(--lz-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(22,163,74,0.35);
}

.saas-btn--outline {
    background: var(--lz-white);
    color: var(--lz-primary) !important;
    border: 2px solid var(--lz-primary);
}
.saas-btn--outline:hover {
    background: var(--lz-primary-5);
    transform: translateY(-2px);
}

.saas-btn--white {
    background: var(--lz-white);
    color: var(--lz-primary) !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}
.saas-btn--white:hover { transform: translateY(-2px); box-shadow: var(--lz-shadow-lg); }

.saas-btn--outline-white {
    background: transparent;
    color: var(--lz-white) !important;
    border: 2px solid rgba(255,255,255,0.4);
}
.saas-btn--outline-white:hover {
    border-color: #fff;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

/* ═══ HERO ═══ */
.saas-hero { padding: 80px 0 60px; }
.hero-badge {
    display: inline-block;
    background: var(--lz-primary-light);
    color: var(--lz-primary);
    padding: 8px 18px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 24px;
}
.hero-title {
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 800;
    line-height: 1.12;
    margin-bottom: 20px;
    letter-spacing: -1px;
}
.hero-lead {
    font-size: 18px;
    color: var(--lz-text-50);
    margin-bottom: 20px;
    line-height: 1.6;
    font-weight: 500;
}
.hero-trust {
    display: flex;
    gap: 20px;
    margin-bottom: 28px;
    font-size: 14px;
    color: var(--lz-text-50);
    font-weight: 500;
}
.hero-trust i { color: var(--lz-primary); font-size: 16px; }

/* Hero Mockup */
.hero-mockup { position: relative; width: 100%; max-width: 500px; }
.mockup-laptop {
    background: var(--lz-dark);
    border-radius: 14px;
    padding: 20px 20px 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.mockup-dash-topbar { display: flex; gap: 6px; margin-bottom: 14px; }
.mockup-dash-topbar span { width: 10px; height: 10px; border-radius: 50%; }
.mockup-dash-topbar span:nth-child(1) { background: #ef4444; }
.mockup-dash-topbar span:nth-child(2) { background: #f59e0b; }
.mockup-dash-topbar span:nth-child(3) { background: #22c55e; }
.mockup-screen { background: #1e293b; border-radius: 8px; padding: 16px; }
.mockup-dash-header { display: flex; justify-content: space-between; margin-bottom: 12px; }
.mockup-dash-title { color: #fff; font-size: 14px; font-weight: 700; }
.mockup-dash-badge { background: #22c55e; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.mockup-dash-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
.mockup-stat-card { background: #334155; padding: 10px 8px; border-radius: 8px; text-align: center; }
.mockup-stat-num { display: block; color: #fff; font-size: 15px; font-weight: 700; }
.mockup-stat-label { display: block; color: #94a3b8; font-size: 10px; margin-top: 2px; }
.mockup-dash-chart { display: flex; align-items: flex-end; gap: 5px; height: 50px; }
.mockup-bar { flex: 1; background: linear-gradient(to top, var(--lz-primary), #4ade80); border-radius: 3px 3px 0 0; }

.mockup-phone {
    position: absolute;
    right: -10px;
    bottom: -40px;
    width: 150px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    border: 3px solid #e2e8f0;
}
.mockup-phone-notch { width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; margin: 8px auto 4px; }
.mockup-phone-screen { padding: 4px 10px 10px; }
.mockup-phone-header { font-size: 13px; font-weight: 800; margin-bottom: 6px; color: var(--lz-primary); }
.mockup-phone-item { font-size: 11px; padding: 5px 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; }
.mockup-phone-item span { color: var(--lz-primary); font-weight: 600; }
.mockup-phone-total { font-size: 12px; font-weight: 800; margin-top: 8px; color: var(--lz-primary); }

/* ═══ METRICS ═══ */
.saas-metrics-strip {
    background: var(--lz-dark);
    padding: 48px 0;
}
.metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center; }
.metric-item { border-right: 1px solid rgba(255,255,255,0.1); padding: 8px 0; }
.metric-item:last-child { border-right: none; }
.metrics-grid h2 { color: #fff; font-size: clamp(36px, 4vw, 48px); font-weight: 900; letter-spacing: -1px; }
.metrics-grid p { color: rgba(255,255,255,0.6); font-size: 14px; font-weight: 600; }

/* ═══ COMMON ═══ */
.saas-section { padding: 80px 0; }
.saas-section--alt { background: var(--lz-bg); }
.section-tag {
    display: inline-block;
    background: var(--lz-primary-light);
    color: var(--lz-primary);
    padding: 6px 16px;
    border-radius: 24px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 16px;
}
.section-title {
    font-size: clamp(24px, 3vw, 36px);
    font-weight: 800;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}
.section-desc { font-size: 16px; color: var(--lz-text-50); line-height: 1.6; }

/* ═══ PRODUCT SHOWCASE ═══ */
.product-showcase { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.showcase-item { text-align: center; padding: 32px 20px; background: var(--lz-white); border-radius: 16px; border: 1px solid var(--lz-border); transition: all 0.2s; }
.showcase-item:hover { transform: translateY(-4px); box-shadow: var(--lz-shadow-md); }
.showcase-icon { font-size: 40px; color: var(--lz-primary); margin-bottom: 16px; }
.showcase-item h4 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
.showcase-item p { font-size: 14px; color: var(--lz-text-50); line-height: 1.5; }

/* ═══ FEATURES ═══ */
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.feature-card {
    background: var(--lz-white);
    border: 1px solid var(--lz-border);
    border-radius: 16px;
    padding: 28px 24px;
    transition: all 0.25s;
}
.feature-card:hover { transform: translateY(-4px); box-shadow: var(--lz-shadow-md); border-color: rgba(22,163,74,0.3); }
.feature-icon { display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 14px; color: #fff; font-size: 24px; margin-bottom: 16px; }
.feature-card h4 { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
.feature-card p { font-size: 14px; color: var(--lz-text-50); line-height: 1.5; }

.bg-blue { background: #3b82f6; }
.bg-purple { background: #8b5cf6; }
.bg-teal { background: #14b8a6; }
.bg-orange { background: #f97316; }
.bg-rose { background: #f43f5e; }
.bg-indigo { background: #6366f1; }

/* ═══ STEPS ═══ */
.steps-grid { display: flex; align-items: flex-start; justify-content: center; gap: 16px; }
.step-card { text-align: center; padding: 24px 16px; flex: 1; max-width: 260px; }
.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: var(--lz-primary);
    color: #fff;
    border-radius: 50%;
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 16px;
}
.step-card h4 { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
.step-card p { font-size: 14px; color: var(--lz-text-50); line-height: 1.5; }
.step-connector { color: var(--lz-text-25); font-size: 28px; padding-top: 16px; }

/* ═══ SUCCESS CARDS ═══ */
.success-card {
    background: var(--lz-white);
    border: 1px solid var(--lz-border);
    border-radius: 16px;
    overflow: hidden;
    height: 100%;
    transition: all 0.25s;
}
.success-card:hover { transform: translateY(-6px); box-shadow: var(--lz-shadow-lg); }
.success-card-img { width: 100%; height: 160px; background-size: cover; background-position: center; }
.success-card-body { padding: 20px; }
.success-card-body strong { font-size: 16px; font-weight: 700; display: block; margin-bottom: 6px; }
.success-card-body p { font-size: 14px; color: var(--lz-text-50); line-height: 1.5; margin: 0; }

/* ═══ TESTIMONIALS ═══ */
.testimonial-card-bubble {
    background: var(--lz-bg);
    border-radius: 16px;
    padding: 28px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.testimonial-stars { color: #f59e0b; font-size: 16px; margin-bottom: 12px; }
.testimonial-text-quote {
    font-size: 14px;
    color: var(--lz-text-85);
    line-height: 1.7;
    font-style: italic;
    margin-bottom: 20px;
}
.testimonial-author-box { display: flex; align-items: center; gap: 12px; }
.author-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
.testimonial-author-box strong { font-size: 14px; font-weight: 700; display: block; }
.testimonial-author-box span { font-size: 12px; color: var(--lz-text-50); display: block; }

/* ═══ PRICING ═══ */
.pricing-card-home {
    background: var(--lz-white);
    border: 2px solid var(--lz-border);
    border-radius: 16px;
    padding: 32px 28px;
    transition: all 0.25s;
    position: relative;
}
.pricing-card-home:hover { transform: translateY(-6px); box-shadow: var(--lz-shadow-lg); }
.pricing-card--featured { border-color: var(--lz-primary); box-shadow: 0 0 0 1px var(--lz-primary), var(--lz-shadow-md); }
.pricing-badge-featured {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--lz-primary);
    color: #fff;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}
.pricing-header-home h4 { font-size: 20px; font-weight: 800; margin-bottom: 12px; }
.pricing-price-box { display: flex; align-items: baseline; justify-content: center; }
.pricing-currency { font-size: 18px; font-weight: 700; color: var(--lz-primary); }
.pricing-amount { font-size: 44px; font-weight: 900; letter-spacing: -1px; }
.pricing-period { font-size: 14px; color: var(--lz-text-50); margin-left: 4px; font-weight: 600; }
.pricing-desc-home { font-size: 14px; color: var(--lz-text-50); margin-bottom: 20px; font-weight: 500; line-height: 1.5; text-align: center; }
.pricing-features-list-home { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
.pricing-features-list-home li { display: flex; gap: 10px; align-items: flex-start; font-size: 14px; color: var(--lz-text-85); line-height: 1.4; }
.pricing-features-list-home li i { font-size: 18px; color: var(--lz-primary); flex-shrink: 0; margin-top: 2px; }

/* ═══ FAQ ═══ */
.faq-accordion { border-top: 1px solid var(--lz-border); }
.faq-item { border-bottom: 1px solid var(--lz-border); }
.faq-question {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 20px 0;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Montserrat', sans-serif;
    color: var(--lz-text-85);
    transition: color 0.2s;
}
.faq-question:hover { color: var(--lz-primary); }
.faq-question i { font-size: 18px; color: var(--lz-primary); transition: transform 0.3s; }
.faq-item.active .faq-question i { transform: rotate(45deg); }
.faq-answer { display: none; padding: 0 0 20px; font-size: 15px; color: var(--lz-text-50); line-height: 1.6; }
.faq-item.active .faq-answer { display: block; }

/* ═══ CTA ═══ */
.saas-cta-final {
    padding: 80px 0;
    background: var(--lz-dark);
    position: relative;
    overflow: hidden;
}
.saas-cta-final::before {
    content: '';
    position: absolute;
    top: -100px; right: -100px;
    width: 300px; height: 300px;
    background: rgba(22,163,74,0.08);
    border-radius: 50%;
}
.saas-cta-final h2 { font-size: clamp(24px, 3vw, 36px); font-weight: 800; color: #fff; margin-bottom: 12px; }
.saas-cta-final p { font-size: 17px; color: rgba(255,255,255,0.6); margin-bottom: 32px; font-weight: 500; }

/* ═══ STICKY MOBILE CTA ═══ */
.sticky-mobile-cta {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    box-shadow: 0 -2px 12px rgba(0,0,0,0.08);
    z-index: 1000;
    border-top: 1px solid var(--lz-border);
}
.sticky-mobile-cta .saas-btn { min-height: 52px; font-size: 16px; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 991px) {
    .saas-section { padding: 60px 0; }
    .saas-hero { padding: 60px 0 40px; }
    .product-showcase { grid-template-columns: repeat(2, 1fr); }
    .features-grid { grid-template-columns: repeat(2, 1fr); }
    .steps-grid { flex-direction: column; align-items: center; }
    .step-connector { transform: rotate(90deg); }
    .metrics-grid { grid-template-columns: 1fr; gap: 16px; }
    .metric-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px; }
    .metric-item:last-child { border-bottom: none; }
    .hero-mockup { max-width: 400px; margin: 0 auto; }
}

@media (max-width: 767px) {
    .product-showcase { grid-template-columns: 1fr; }
    .features-grid { grid-template-columns: 1fr; }
    .hero-title { font-size: 32px; }
    .hero-trust { flex-direction: column; gap: 8px; align-items: center; }
    .saas-actions { flex-direction: column; }
    .saas-actions .saas-btn { width: 100%; justify-content: center; }
    .sticky-mobile-cta { display: block; }
    .mockup-phone { display: none; }
    .saas-cta-final { padding: 60px 0; }
    .saas-footer-dark { padding-bottom: 80px; }
}
</style>
@endpush

@push('json-ld')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "{{ gs()->siteName() }}",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web, Android, iOS",
    "url": "{{ route('home') }}",
    "description": "SaaS de gestión para restaurantes con pedidos por aplicativo, POS, cocina, delivery, inventario, facturación y reportes."
}
</script>
@endpush

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animated counters
    const counters = document.querySelectorAll('.counter');
    const observerOptions = { threshold: 0.5 };

    const counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-target'));
                let current = 0;
                const increment = target / 60;
                const timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        el.textContent = target;
                        clearInterval(timer);
                    } else {
                        el.textContent = Math.floor(current);
                    }
                }, 30);
                counterObserver.unobserve(el);
            }
        });
    }, observerOptions);

    counters.forEach(function(counter) { counterObserver.observe(counter); });

    // Sticky CTA visibility
    var stickyCta = document.getElementById('stickyCta');
    if (stickyCta) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 600) {
                stickyCta.style.transform = 'translateY(0)';
                stickyCta.style.opacity = '1';
            } else {
                stickyCta.style.transform = 'translateY(100%)';
                stickyCta.style.opacity = '0';
            }
        });
        stickyCta.style.transform = 'translateY(100%)';
        stickyCta.style.opacity = '0';
        stickyCta.style.transition = 'all 0.3s ease';
    }
});
</script>
@endpush
