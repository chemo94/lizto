@extends('admin.layouts.master')
@section('content')
    <main class="account">
        <span class="account__overlay bg-img dark-bg"
            data-background-image="{{ asset('assets/admin/images/login-dark.png') }}"></span>
        <span class="account__overlay bg-img light-bg"
            data-background-image="{{ asset('assets/admin/images/login-bg.png') }}"></span>
        <div class="account__card" style="max-width: 500px;">
            <div class="account__logo">
                <img src="{{ siteLogo() }}" class="light-show" alt="brand-thumb">
                <img src="{{ siteLogo('dark') }}" class="dark-show" alt="brand-thumb">
            </div>
            <h2 class="account__title">Crear Cuenta de Flota 👋</h2>
            <p class="account__desc">Regístrate para crear tu propia flota y conductores en Lizto.</p>
            
            <div class="card mb-3 text-start" style="border: 1px solid #7c62e3; border-radius: 8px; background-color: #f9f8ff;">
                <div class="card-body p-3">
                    <h6 style="color: #7c62e3; font-weight: 600;" class="mb-2"><i class="las la-award fs-18"></i> Plan Lizto Partner</h6>
                    <p class="small text-muted mb-2" style="font-size: 13px;">Ideal para iniciar rápidamente sin desarrollar una aplicación propia.</p>
                    <ul class="small mb-0 ps-3 text-muted" style="list-style-type: disc; font-size: 12px; line-height: 1.6;">
                        <li><strong>Inversión inicial:</strong> USD 199 (Activación, configuración de ciudad/tarifas, panel y capacitación).</li>
                        <li><strong>Suscripción mensual:</strong> USD 69/mes (Servidores, copias de seguridad, actualizaciones y soporte técnico).</li>
                        <li><strong>Tus Reglas:</strong> Todas las comisiones por viaje son percibidas por ti. Lizto no retiene porcentaje de los viajes.</li>
                    </ul>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('fleet.register.submit') }}" method="POST" class="account__form">
                @csrf
                <div class="row">
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Nombre</label>
                        <input type="text" class="form--control h-40" name="firstname" value="{{ old('firstname') }}" required>
                    </div>
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Apellido</label>
                        <input type="text" class="form--control h-40" name="lastname" value="{{ old('lastname') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Usuario</label>
                        <input type="text" class="form--control h-40" name="username" value="{{ old('username') }}" required>
                    </div>
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Teléfono</label>
                        <input type="text" class="form--control h-40" name="phone" value="{{ old('phone') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form--label">Email</label>
                    <input type="email" class="form--control h-40" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label class="form--label">Ciudad de Operación</label>
                    <select class="form--control h-40" name="zone_id" required style="padding: 0 10px;">
                        <option value="">Selecciona tu ciudad...</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ __($zone->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form--label">Nombre de tu Flota</label>
                    <input type="text" class="form--control h-40" name="fleet_name" value="{{ old('fleet_name') }}" placeholder="Ej: Mototaxis del Norte" required>
                </div>
                <div class="row">
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Contraseña</label>
                        <input type="password" class="form--control h-40" name="password" required>
                    </div>
                    <div class="col-sm-6 form-group">
                        <label class="form--label">Confirmar</label>
                        <input type="password" class="form--control h-40" name="password_confirmation" required>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn--primary w-100 h-48 mb-2 fs-16">
                        Registrar Flota
                    </button>
                    <div class="text-center mt-2">
                        <a href="{{ route('fleet.login') }}" class="forgot-password">¿Ya tienes una cuenta? Iniciar Sesión</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection
