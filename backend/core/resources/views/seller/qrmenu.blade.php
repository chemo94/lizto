@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-qrcode"></i></span> QR de Carta
@endsection

@section('seller-content')
<div class="s-content">
    <div class="s-grid-2" style="align-items:start">

        <!-- QR CODE -->
        <div class="s-card" style="text-align:center">
            <h3 class="s-card-title" style="justify-content:center"><i class="las la-qrcode"></i> Código QR de tu Menú</h3>
            <p style="color:var(--s-text-3);font-size:13px;margin:0 0 20px">Escanea este código para ver el menú digital de tu tienda</p>

            <div style="position:relative;display:inline-block;margin:0 auto">
                <div style="background:white;border-radius:20px;padding:16px;border:2px solid var(--s-border);box-shadow:var(--s-shadow)">
                    <img id="qr-code-img" src="{{ $qrUrl }}" alt="QR del Menú" style="width:220px;height:220px;display:block;border-radius:8px">
                </div>
                <div style="position:absolute;inset:-1px;border-radius:21px;pointer-events:none;border:2px solid transparent;background:linear-gradient(var(--s-surface),var(--s-surface)) padding-box, linear-gradient(135deg,#22c55e,#3b82f6) border-box"></div>
            </div>

            <div style="margin-top:20px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:12px;padding:10px 16px;font-size:12px;color:var(--s-text-3);word-break:break-all;text-align:left;margin-bottom:16px">
                <i class="las la-link" style="margin-right:4px"></i>
                <span>{{ $storeUrl }}</span>
            </div>

            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                <button class="s-btn s-btn-primary" onclick="downloadQR()">
                    <i class="las la-download"></i> Descargar QR
                </button>
                <a href="{{ $storeUrl }}" class="s-btn s-btn-outline" target="_blank">
                    <i class="las la-external-link-alt"></i> Ver Menú
                </a>
            </div>
        </div>

        <!-- PRINTABLE POSTER -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-print"></i> Cartel Imprimible</h3>
            <p style="color:var(--s-text-3);font-size:13px;margin:0 0 20px">Listo para imprimir y colocar en tus mesas o mostrador</p>

            <!-- Preview -->
            <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px dashed #16a34a;border-radius:18px;padding:32px 24px;text-align:center;max-width:320px;margin:0 auto 20px;box-shadow:0 10px 25px rgba(22,163,74,0.05)" id="printable-poster">
                <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:16px">
                    @if($store && $store->image)
                    <img src="{{ getImage('assets/images/store/' . $store->image) }}" alt="Logo" style="width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid rgba(22,163,74,0.15)">
                    @else
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#22c55e,#16a34a);display:grid;place-items:center;font-size:22px;flex-shrink:0">🏪</div>
                    @endif
                    <div style="text-align:left">
                        <div style="font-size:16px;font-weight:900;color:#14532d;letter-spacing:-.3px">{{ $store->name ?? 'Mi Tienda' }}</div>
                        <div style="font-size:10px;color:#16a34a;font-weight:800;text-transform:uppercase;letter-spacing:1px">Menú digital</div>
                    </div>
                </div>
                <div style="font-size:12px;color:#16a34a;margin-bottom:16px;font-weight:800;display:flex;align-items:center;justify-content:center;gap:4px">
                    <i class="las la-mobile-alt" style="font-size:16px"></i> Escanea para ver nuestro menú
                </div>
                <div style="background:white;border-radius:16px;padding:14px;display:inline-block;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(22,163,74,0.1)">
                    <img src="{{ $qrUrl }}" alt="QR" style="width:160px;height:160px;border-radius:8px;display:block">
                </div>
                <div style="font-size:11px;color:#14532d;margin-top:16px;word-break:break-all;font-weight:600;background:rgba(22,163,74,0.05);padding:6px 12px;border-radius:8px;border:1px solid rgba(22,163,74,0.1)">{{ $storeUrl }}</div>
            </div>

            <!-- Style options -->
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                <button class="s-btn s-btn-primary" onclick="printPoster()">
                    <i class="las la-print"></i> Imprimir Cartel
                </button>
            </div>
        </div>
    </div>

    <!-- HOW TO USE -->
    <div class="s-card" style="margin-top:20px">
        <h3 class="s-card-title"><i class="las la-info-circle"></i> ¿Cómo usar tu QR?</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
            @foreach([
                ['icon'=>'la-print','title'=>'Imprime el QR','desc'=>'Descarga e imprime el cartel para colocarlo en mesas, mostrador o empaque.'],
                ['icon'=>'la-mobile-alt','title'=>'Comparte online','desc'=>'Copia el link y compártelo por WhatsApp, Instagram o Facebook.'],
                ['icon'=>'la-qrcode','title'=>'Escanea y ordena','desc'=>'Tus clientes escanean y ven el menú completo desde su celular.'],
            ] as $step)
            <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="width:40px;height:40px;border-radius:12px;background:var(--s-accent-light);display:grid;place-items:center;font-size:20px;color:var(--s-accent-dark);flex-shrink:0">
                    <i class="las {{ $step['icon'] }}"></i>
                </div>
                <div>
                    <b style="font-size:13px;color:var(--s-text)">{{ $step['title'] }}</b>
                    <p style="font-size:12px;color:var(--s-text-3);margin:4px 0 0;line-height:1.5">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('script')
<script>
function printPoster() {
    var poster = document.getElementById('printable-poster').outerHTML;
    var w = window.open('', '_blank', 'width=600,height=800');
    w.document.write('<!doctype html><html><head><title>QR — {{ $store->name ?? "Menú" }}</title>');
    w.document.write('<style>');
    w.document.write('@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&display=swap");');
    w.document.write('*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}');
    w.document.write('body{display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0fdf4;font-family:"Plus Jakarta Sans",sans-serif}');
    w.document.write('#printable-poster{box-shadow: 0 20px 40px rgba(0,0,0,0.05); transition: none !important;}');
    w.document.write('@media print{');
    w.document.write('  body{background:white;}');
    w.document.write('  #printable-poster{');
    w.document.write('    border: 3px dashed #16a34a !important;');
    w.document.write('    background: linear-gradient(135deg,#f0fdf4,#dcfce7) !important;');
    w.document.write('    max-width: 480px !important;');
    w.document.write('    width: 100% !important;');
    w.document.write('    padding: 40px !important;');
    w.document.write('    transform: scale(1.15);');
    w.document.write('  }');
    w.document.write('}');
    w.document.write('</style></head><body>'+poster+'</body></html>');
    w.document.write('<script>setTimeout(function(){ window.print(); }, 800);<\/script>');
    w.document.close();
}
function downloadQR() {
    var img = document.querySelector('#qr-code-img');
    if (!img) return;
    var link = document.createElement('a');
    link.download = 'qr-menu-{{ $store->slug ?? "tienda" }}.svg';
    link.href = img.src;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endpush
@endsection
