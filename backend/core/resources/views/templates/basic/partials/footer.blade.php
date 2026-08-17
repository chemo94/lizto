@php
    $footerContent = @getContent('footer.content', true)->data_values;
    $contactContent = @getContent('contact_us.content', true)->data_values;
    $links = getContent('policy_pages.element', orderById: true);
    $sIcons = getContent('social_icon.element', orderById: true);
@endphp

<!-- SUPPORT BANNER SECTION -->
<section class="saas-support-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <h3>Estamos disponibles <span class="text-primary font-weight-bold">siempre que nos necesites.</span></h3>
                <p>Nuestro <strong class="text-uppercase text-primary">Soporte en Línea 21/7</strong> te respalda a toda hora. ¡Una atención continua para que la gestión de tu restaurante nunca se detenga!</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                <div class="support-avatar-wrap">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&auto=format&fit=crop&q=80" alt="Soporte Lizto" class="support-avatar-img">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MAIN FOOTER -->
<footer class="saas-footer-dark">
    <div class="container">
        <div class="row gy-5">
            <!-- Column 1: Logo & Desc -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-brand">
                    <img src="{{ siteLogo('dark') }}" alt="Lizto Logo" class="footer-logo">
                    <p class="footer-desc-text mt-3">
                        Nuestro software agilizará la velocidad de tu negocio en todo el proceso de ventas y atención al cliente.
                    </p>
                </div>
            </div>

            <!-- Column 2: Redes -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Otros enlaces</h5>
                <div class="footer-social-links mt-3">
                    @foreach ($sIcons as $sIcon)
                        <a href="{{ @$sIcon->data_values->url }}" target="_blank" class="social-link-icon">
                            @php echo @$sIcon->data_values->social_icon; @endphp
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Column 3: Enlaces de interés -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Enlaces de interés</h5>
                <ul class="footer-links-list mt-3">
                    <li><a href="{{ route('negocios') }}#precios">Nuestros Precios</a></li>
                    <li><a href="{{ route('home') }}#herramientas">Herramientas</a></li>
                    <li><a href="{{ route('negocios') }}">Complementarios Disponibles</a></li>
                    <li><a href="{{ route('contact') }}">Resellers</a></li>
                    <li><a href="{{ route('home') }}">Centro de Descargas</a></li>
                </ul>
            </div>

            <!-- Column 4: Otros Enlaces -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Otros Enlaces</h5>
                <ul class="footer-links-list mt-3">
                    <li><a href="{{ route('contact') }}">Contáctenos</a></li>
                    <li>
                        <a href="{{ route('complaints.form') }}" class="d-flex align-items-center gap-2">
                            <i class="las la-book text-primary"></i> Libro de Reclamaciones
                        </a>
                    </li>
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ route('policy.pages', @$link->slug) }}">
                                {{ __(@$link->data_values->title) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="footer-bottom-bar mt-5 pt-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="copyright-text mb-0">&copy; {{ date('Y') }} {{ gs('site_name') }}. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <p class="developer-text mb-0">Desarrollado por {{ gs('site_name') }}</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- WHATSAPP FLOATING BUTTON -->
<a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20solicitar%20una%20demo%20de%20Lizto" target="_blank" class="whatsapp-float-btn" aria-label="Soporte WhatsApp">
    <i class="lab la-whatsapp"></i>
</a>

@push('style')
<style>
    /* SUPPORT BANNER */
    .saas-support-banner {
        background: #f0fdf4;
        padding: 60px 0;
        border-top: 1px solid var(--lz-border);
        position: relative;
    }
    .saas-support-banner h3 {
        font-size: 28px;
        font-weight: 800;
        color: var(--lz-text-85);
        margin-bottom: 12px;
    }
    .saas-support-banner p {
        font-size: 16px;
        color: var(--lz-text-50);
        margin: 0;
        line-height: 1.6;
    }
    .support-avatar-wrap {
        display: inline-block;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: var(--lz-shadow-lg);
        overflow: hidden;
    }
    .support-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* FOOTER DARK */
    .saas-footer-dark {
        background: #222222;
        color: #ffffff;
        padding: 80px 0 40px;
        border-top: 5px solid var(--lz-primary);
    }
    .footer-logo {
        max-width: 160px;
        height: auto;
        filter: brightness(0) invert(1);
    }
    .footer-desc-text {
        font-size: 13.5px;
        color: #aaaaaa;
        line-height: 1.6;
    }
    .footer-title {
        font-size: 16px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .footer-social-links {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .social-link-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.2s;
    }
    .social-link-icon:hover {
        background: var(--lz-primary);
        color: #ffffff !important;
        transform: translateY(-2px);
    }
    .footer-links-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .footer-links-list a {
        font-size: 14px;
        color: #aaaaaa;
        text-decoration: none !important;
        transition: color 0.2s;
    }
    .footer-links-list a:hover {
        color: var(--lz-primary);
    }
    .footer-bottom-bar {
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .copyright-text, .developer-text {
        font-size: 13px;
        color: #777777;
    }

    /* WHATSAPP FLOAT */
    .whatsapp-float-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 56px;
        height: 56px;
        background: #25d366;
        color: #ffffff !important;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        box-shadow: 0 4px 16px rgba(37,211,102,0.3);
        z-index: 9999;
        transition: all 0.2s;
    }
    .whatsapp-float-btn:hover {
        transform: scale(1.08) translateY(-2px);
        box-shadow: 0 6px 20px rgba(37,211,102,0.4);
    }
</style>
@endpush

@push('script')
<script>
    (function($) {
        "use strict";
        $(".subscribe-form").on('submit', function(e) {
            e.preventDefault();
            const email = $(this).find('input[name="email"]').val();
            $.ajax({
                url: "{{ route('subscribe') }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: {
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        $('input[name="email"]').val('');
                        notify('success', response.message);
                    } else {
                        notify('error', response.error);
                    }
                }
            });
        });
    })(jQuery);
</script>
@endpush
