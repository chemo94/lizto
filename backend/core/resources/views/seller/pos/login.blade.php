@extends($activeTemplate . 'layouts.frontend')
@section('content')
<style>
.sl-page{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0fdf4,#f8fff8);padding:92px 20px 40px}
.sl-box{width:100%;max-width:900px;background:#fff;border-radius:20px;padding:40px 36px;box-shadow:0 12px 50px rgba(7,83,33,.1)}
.sl-box h2{font-size:24px;font-weight:800;color:#1a2e1a;margin:0;text-align:center}
.sl-box>p{font-size:13px;color:#68736c;margin:4px 0 24px;text-align:center}
.sl-tabs{display:flex;gap:4px;background:#f0f4f0;border-radius:12px;padding:4px;margin-bottom:24px}
.sl-tab{flex:1;padding:10px;border:none;background:transparent;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;color:#68736c;transition:.15s}
.sl-tab.active{background:#fff;color:#1a2e1a;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.sl-form{display:none}.sl-form.active{display:block}
.sl-field{margin-bottom:14px;text-align:left}.sl-field label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px}.sl-field input,.sl-field select{width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:12px;font-size:14px;box-sizing:border-box;font-family:inherit}.sl-field input:focus,.sl-field select:focus{outline:0;border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1)}.sl-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.sl-btn{width:100%;padding:14px;border:none;border-radius:14px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}.sl-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.3)}
.sl-error{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:14px}.sl-success{background:#dcfce7;color:#166534;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:14px}
.sl-back{display:block;margin-top:16px;color:#16a34a;font-size:13px;font-weight:700;text-decoration:none;text-align:center}
.sunat-row{display:flex;gap:6px}.sunat-row input{flex:1}.sunat-row button{padding:10px 16px;border:none;border-radius:10px;background:#3b82f6;color:#fff;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}
.sl-plans{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:8px 0 18px}.sl-plan{display:block;border:2px solid #e5e7eb;border-radius:14px;padding:14px;cursor:pointer;transition:.18s;background:#fff;position:relative}.sl-plan:has(input:checked){border-color:#16a34a;background:#f0fdf4;box-shadow:0 0 0 3px rgba(22,163,74,.1)}.sl-plan input{position:absolute;opacity:0}.sl-plan b{display:block;font-size:14px;color:#172554}.sl-plan strong{display:block;font-size:20px;color:#16a34a;margin:5px 0}.sl-plan small{display:block;font-size:11px;color:#64748b;line-height:1.35}.sl-plan-trial{display:inline-block!important;color:#15803d!important;background:#dcfce7;border-radius:999px;padding:3px 7px;margin-top:7px;font-weight:800}.sl-plan.delivery{border-color:#bfdbfe}.sl-plan.delivery strong{color:#2563eb}@media(max-width:760px){.sl-box{padding:28px 18px}.sl-plans{grid-template-columns:1fr 1fr}.sl-row{grid-template-columns:1fr}}@media(max-width:440px){.sl-plans{grid-template-columns:1fr}}
</style>

<div class="sl-page">
    <div class="sl-box">
        <i class="las la-store-alt" style="font-size:48px;color:#16a34a;display:block;text-align:center;margin-bottom:8px"></i>
        <h2 style="text-align:center;font-size:20px;font-weight:800;margin:12px 0 4px">Panel Vendedor</h2>
        <p style="text-align:center;font-size:13px;color:#68736c;margin:0 0 20px">¿Lizto para vender?</p>
        <div class="sl-tabs">
            <button class="sl-tab active" onclick="switchTab('login')">Iniciar Sesión</button>
            <button class="sl-tab" onclick="switchTab('register')">Registrarse</button>
        </div>

        @if(session('error'))
            <div class="sl-error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="sl-success">{{ session('success') }}</div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('seller.web.login') }}" class="sl-form active" id="tab-login">
            @csrf
            <div class="sl-field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" placeholder="vendedor@ejemplo.com" required autofocus></div>
            <div class="sl-field"><label>Contraseña</label><input type="password" name="password" placeholder="••••••" required></div>
            <button type="submit" class="sl-btn">Iniciar Sesión</button>
        </form>

        <!-- Register Form -->
        <form method="POST" action="{{ route('seller.register') }}" class="sl-form" id="tab-register">
            @csrf
            @php $registrationPlans = \App\Models\BusinessPackage::active()->orderBy('sort_order')->get(); @endphp
            <div class="sl-field">
                <label>¿Cómo deseas usar Lizto? *</label>
                <div class="sl-plans">
                    @foreach($registrationPlans as $plan)
                    <label class="sl-plan {{ $plan->service_mode === 'delivery_only' ? 'delivery' : '' }}">
                        <input type="radio" name="package_id" value="{{ $plan->id }}" data-mode="{{ $plan->service_mode }}" {{ (string)old('package_id') === (string)$plan->id ? 'checked' : '' }} required>
                        <b>{{ $plan->name }}</b>
                        <strong>S/ {{ number_format($plan->price, 2) }}</strong>
                        <small>por 30 días</small>
                        <small>{{ $plan->description }}</small>
                        <small class="sl-plan-trial">Primer mes gratis</small>
                    </label>
                    @endforeach
                </div>
                <input type="hidden" name="service_mode" id="reg-service-mode" value="{{ old('service_mode') }}">
            </div>
            <div class="sl-row">
                <div class="sl-field"><label>Nombre *</label><input type="text" name="name" value="{{ old('name') }}" required></div>
                <div class="sl-field"><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" required></div>
            </div>
            <div class="sl-row">
                <div class="sl-field"><label>Contraseña *</label><input type="password" name="password" placeholder="Mínimo 6 caracteres" required></div>
                <div class="sl-field"><label>Teléfono *</label><input type="text" name="phone" value="{{ old('phone') }}" placeholder="999 999 999" required></div>
            </div>
            <div class="sl-field">
                <label>Buscar por RUC (opcional)</label>
                <div class="sunat-row">
                    <input type="text" id="reg-ruc" name="ruc_number" placeholder="20123456789">
                    <button type="button" onclick="searchRucReg()"><i class="las la-search"></i> SUNAT</button>
                </div>
                <div id="reg-ruc-result" style="font-size:11px;color:var(--pmt);margin-top:4px"></div>
            </div>
            <div class="sl-row">
                <div class="sl-field"><label>Razón Social</label><input type="text" name="business_name" id="reg-biz-name" value="{{ old('business_name') }}" placeholder="Nombre de la empresa"></div>
                <div class="sl-field"><label>Nombre Comercial</label><input type="text" name="trade_name" id="reg-trade" value="{{ old('trade_name') }}"></div>
            </div>
            <div class="sl-field"><label>Dirección del Negocio *</label>
                <input type="text" id="reg-address" name="address" value="{{ old('address') }}" placeholder="Buscar dirección..." required autocomplete="off">
            </div>
            <div class="sl-row">
                <div class="sl-field"><label>Latitud</label><input type="text" id="reg-lat" name="latitude" value="{{ old('latitude') }}" readonly></div>
                <div class="sl-field"><label>Longitud</label><input type="text" id="reg-lng" name="longitude" value="{{ old('longitude') }}" readonly></div>
            </div>
            <div class="sl-field">
                <label>Zona *</label>
                <select name="zone_id" required>
                    <option value="">Seleccionar zona...</option>
                    @foreach(\App\Models\Zone::active()->get() as $z)
                    <option value="{{ $z->id }}" {{ old('zone_id') == $z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sl-field">
                <label>Tipo de Negocio *</label>
                <select name="store_type" required>
                    <option value="">Seleccionar tipo...</option>
                    @foreach(\App\Models\Store::types() as $key => $label)
                    <option value="{{ $key }}" {{ old('store_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="sl-btn">Crear cuenta y activar mes gratis</button>
        </form>

        <a href="{{ route('home') }}" class="sl-back"><i class="las la-arrow-left"></i> Volver al inicio</a>
    </div>
</div>

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
function switchTab(tab){
    document.querySelectorAll('.sl-tab').forEach(function(t){t.classList.remove('active')});
    document.querySelectorAll('.sl-form').forEach(function(f){f.classList.remove('active')});
    document.querySelector('.sl-tab[onclick*='+tab+']').classList.add('active');
    document.getElementById('tab-'+tab).classList.add('active');
}
function searchRucReg(){
    var num=document.getElementById('reg-ruc').value.trim();
    if(!num||num.length<11){alert('Ingresa un RUC válido (11 dígitos)');return}
    var r=document.getElementById('reg-ruc-result');r.innerHTML='Consultando SUNAT...';
    fetch('/seller/sunat-lookup',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:JSON.stringify({numdoc:num,tpdoc:'6'})})
    .then(function(x){return x.json()}).then(function(d){
        if(d.status&&d.nombre){document.getElementById('reg-biz-name').value=d.nombre;document.getElementById('reg-trade').value=d.nombreComercial||'';document.getElementById('reg-address').value=d.direccion||'';r.innerHTML='<span style="color:#16a34a">✓ '+d.nombre+'</span>'}
        else{r.innerHTML='<span style="color:#dc2626">'+ (d.result||'No encontrado') +'</span>'}
    }).catch(function(){r.innerHTML='<span style="color:#dc2626">Error</span>'})
}
window.addEventListener('load',function(){
    document.querySelectorAll('input[name="package_id"]').forEach(function(input){
        input.addEventListener('change',function(){document.getElementById('reg-service-mode').value=this.dataset.mode})
        if(input.checked) document.getElementById('reg-service-mode').value=input.dataset.mode
    });
    var a=document.getElementById('reg-address');
    if(a&&window.google&&google.maps&&google.maps.places){
        new google.maps.places.Autocomplete(a,{componentRestrictions:{country:'pe'}}).addListener('place_changed',function(){
            var p=this.getPlace();if(p.geometry){document.getElementById('reg-lat').value=p.geometry.location.lat();document.getElementById('reg-lng').value=p.geometry.location.lng()}
        })
    }
    var rf=document.getElementById('tab-register');
    if(rf){
        rf.addEventListener('submit',function(e){
            var addr=document.getElementById('reg-address');
            var lat=document.getElementById('reg-lat');
            var lng=document.getElementById('reg-lng');
            var msg=document.getElementById('reg-address-error');
            if(!msg){msg=document.createElement('div');msg.id='reg-address-error';msg.className='sl-error';addr.parentNode.insertBefore(msg,addr.nextSibling)}
            var addrVal=(addr.value||'').trim();
            if(!addrVal||addrVal==='-'||!lat.value||!lng.value){
                e.preventDefault();
                msg.textContent='Debe buscar y seleccionar una dirección válida del autocompletado de Google Maps';
                return
            }
            msg.textContent=''
        })
    }
});
@if($errors->any()) switchTab('register'); @endif
</script>
@endpush
@endsection
