@extends('admin.layouts.master')
@section('content')
    <main class="account">
        <span class="account__overlay bg-img dark-bg"
            data-background-image="{{ asset('assets/admin/images/login-dark.png') }}"></span>
        <span class="account__overlay bg-img light-bg"
            data-background-image="{{ asset('assets/admin/images/login-bg.png') }}"></span>
        <div class="account__card">
            <div class="account__logo">
                <img src="{{ siteLogo() }}" class="light-show" alt="brand-thumb">
                <img src="{{ siteLogo('dark') }}" class="dark-show" alt="brand-thumb">
            </div>
            <h2 class="account__title">Panel de Flota 👋</h2>
            <p class="account__desc">Ingresa tus credenciales de administrador de flota para continuar.</p>
            <form action="{{ route('fleet.web.login') }}" method="POST" class="account__form">
                @csrf
                <div class="form-group">
                    <label class="form--label">Email</label>
                    <input type="email" class="form--control h-48" value="{{ old('email') }}" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form--label">Contraseña</label>
                    <div class="position-relative">
                        <input id="password" name="password" required type="password" class="form--control h-48">
                        <span class="password-show-hide fas toggle-password fa-eye-slash" id="#password"></span>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn--primary w-100 h-48 mb-2 fs-16">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar Sesión
                    </button>
                    <div class="text-center mt-3">
                        <p class="mb-0 fs-13 text-muted">¿Quieres administrar tu propia flota?</p>
                        <a href="{{ route('fleet.register') }}" class="text--primary fw-bold fs-14">Regístrate como Dueño de Flota</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection
