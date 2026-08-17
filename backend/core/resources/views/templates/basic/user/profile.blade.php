@extends('Template::layouts.frontend')
@section('content')
<style>
.upanel{padding:100px 0 60px;min-height:100vh;background:#f8fdf8}
.upanel .container{max-width:700px}
.upanel-head{margin-bottom:28px}
.upanel-head h1{font-size:28px;font-weight:800;color:#1a2e1a;margin:0 0 6px}
.upanel-head p{color:#68736c;margin:0;font-size:15px}
.upanel-back{display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:700;font-size:13px;text-decoration:none;margin-bottom:20px}
.upanel-back:hover{color:#15803d}
.upanel-card{background:#fff;border:1px solid #e0eee2;border-radius:16px;padding:28px;margin-bottom:24px}
.upanel-card h3{font-size:17px;font-weight:800;color:#1a2e1a;margin:0 0 20px;display:flex;align-items:center;gap:8px}
.upanel-card h3 i{color:#16a34a}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px}
.form-group input{width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;color:#1a2e1a;background:#fff;transition:.2s;box-sizing:border-box}
.form-group input:focus{outline:0;border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.form-group input:disabled{background:#f3f4f6;color:#6b7280}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-submit{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:.2s;margin-top:8px}
.form-submit:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.3)}
.upanel-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;display:grid;place-items:center;font-size:36px;margin:0 auto 20px}
.upanel-alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px}
.upanel-alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.upanel-alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.upanel-nav{display:flex;gap:12px;margin-top:20px}
.upanel-nav a{padding:10px 18px;border:1px solid #e0eee2;border-radius:10px;text-decoration:none;color:#374151;font-weight:700;font-size:13px;transition:.2s}
.upanel-nav a:hover{border-color:#16a34a;color:#16a34a}
.upanel-nav a.active{background:#16a34a;color:#fff;border-color:#16a34a}
</style>

<div class="upanel">
    <div class="container">
        <a href="{{ route('user.dashboard') }}" class="upanel-back"><i class="las la-arrow-left"></i> Volver al Panel</a>

        <div class="upanel-head">
            <h1>Mi Perfil</h1>
            <p>Administra tu información personal</p>
        </div>

        @if(session('success'))
            <div class="upanel-alert upanel-alert-success"><i class="las la-check-circle"></i> {{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="upanel-alert upanel-alert-error"><i class="las la-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif

        <!-- Avatar -->
        <div class="upanel-card" style="text-align:center">
            <div class="upanel-avatar">
                {{ strtoupper(substr($user->firstname,0,1)) }}{{ strtoupper(substr($user->lastname,0,1)) }}
            </div>
            <h3 style="justify-content:center">{{ $user->firstname }} {{ $user->lastname }}</h3>
            <p style="color:#68736c;font-size:13px;margin:0">@{{ $user->username }}</p>
        </div>

        <!-- Profile Form -->
        <div class="upanel-card">
            <h3><i class="las la-user-edit"></i> Información Personal</h3>
            <form method="POST" action="{{ route('user.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="firstname" value="{{ old('firstname', $user->firstname) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido</label>
                        <input type="text" name="lastname" value="{{ old('lastname', $user->lastname) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}" placeholder="+51 999 999 999">
                </div>
                <button type="submit" class="form-submit"><i class="las la-save"></i> Guardar Cambios</button>
            </form>
        </div>

        <!-- Account Info -->
        <div class="upanel-card">
            <h3><i class="las la-info-circle"></i> Información de la Cuenta</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" value="{{ $user->username }}" disabled>
                </div>
                <div class="form-group">
                    <label>Miembro desde</label>
                    <input type="text" value="{{ $user->created_at->format('d/m/Y') }}" disabled>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <div class="upanel-nav">
            <a href="{{ route('user.dashboard') }}"><i class="las la-tachometer-alt"></i> Mi Panel</a>
            <a href="{{ route('user.wallet') }}"><i class="las la-wallet"></i> Billetera</a>
        </div>
    </div>
</div>
@endsection
