@extends('Template::layouts.frontend')
@section('content')
<style>
.upanel{padding:100px 0 60px;min-height:100vh;background:#f8fdf8}
.upanel .container{max-width:960px}
.upanel-head{margin-bottom:32px}
.upanel-head h1{font-size:28px;font-weight:800;color:#1a2e1a;margin:0 0 6px}
.upanel-head p{color:#68736c;margin:0;font-size:15px}
.upanel-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px}
.upanel-stat{background:#fff;border:1px solid #e0eee2;border-radius:16px;padding:22px;text-align:center}
.upanel-stat i{font-size:28px;color:#16a34a;margin-bottom:10px;display:block}
.upanel-stat strong{font-size:30px;font-weight:800;color:#1a2e1a;display:block}
.upanel-stat span{font-size:12px;color:#68736c;text-transform:uppercase;font-weight:700;letter-spacing:.5px}
.upanel-card{background:#fff;border:1px solid #e0eee2;border-radius:16px;padding:24px;margin-bottom:24px}
.upanel-card h3{font-size:17px;font-weight:800;color:#1a2e1a;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.upanel-card h3 i{color:#16a34a}
.upanel-wallet{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:16px;padding:28px;margin-bottom:24px}
.upanel-wallet-info h3{font-size:14px;font-weight:600;opacity:.85;margin:0 0 4px}
.upanel-wallet-info strong{font-size:32px;font-weight:800}
.upanel-wallet a{background:rgba(255,255,255,.2);color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;transition:.2s}
.upanel-wallet a:hover{background:rgba(255,255,255,.3)}
.order-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f0f5f0;text-decoration:none;color:inherit;transition:.15s}
.order-row:hover{background:#f0fdf4;margin:0 -12px;padding:14px 12px;border-radius:10px}
.order-row:last-child{border:0}
.order-row-left{display:flex;align-items:center;gap:12px}
.order-row-left i{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:18px;color:#16a34a;background:#f0fdf4}
.order-row-left strong{font-size:14px;color:#1a2e1a;display:block}
.order-row-left small{font-size:12px;color:#68736c}
.order-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase}
.badge-pending{background:#fef3c7;color:#92400e}
.badge-active{background:#dcfce7;color:#166534}
.badge-completed{background:#e0e7ff;color:#3730a3}
.badge-cancelled{background:#fee2e2;color:#991b1b}
.upanel-empty{text-align:center;padding:40px;color:#68736c}
.upanel-empty i{font-size:48px;color:#d1d5db;margin-bottom:12px;display:block}
.upanel-menu{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:20px}
.upanel-menu a{display:flex;align-items:center;gap:10px;padding:14px 12px;border:1px solid #e0eee2;border-radius:12px;text-decoration:none;color:#1a2e1a;font-weight:700;font-size:13px;transition:.2s;justify-content:center}
.upanel-menu a:hover{border-color:#16a34a;background:#f0fdf4;color:#16a34a}
.upanel-menu a i{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;font-size:18px;color:#16a34a;background:#f0fdf4}
</style>

<div class="upanel">
    <div class="container">
        <div class="upanel-head">
            <h1>Hola, {{ $user->firstname }}</h1>
            <p>Bienvenido a tu panel de control</p>
        </div>

        <!-- Wallet Banner -->
        <div class="upanel-wallet">
            <div class="upanel-wallet-info">
                <h3><i class="las la-wallet"></i> Mi Billetera</h3>
                <strong>S/ {{ number_format($walletBalance, 2) }}</strong>
            </div>
            <a href="{{ route('user.wallet') }}"><i class="las la-arrow-right"></i> Ver detalles</a>
        </div>

        <!-- Stats -->
        <div class="upanel-grid">
            <div class="upanel-stat">
                <i class="las la-shopping-bag"></i>
                <strong>{{ $orderStats['total'] }}</strong>
                <span>Total Pedidos</span>
            </div>
            <div class="upanel-stat">
                <i class="las la-clock"></i>
                <strong>{{ $orderStats['pending'] }}</strong>
                <span>Pendientes</span>
            </div>
            <div class="upanel-stat">
                <i class="las la-truck"></i>
                <strong>{{ $orderStats['active'] }}</strong>
                <span>En Curso</span>
            </div>
            <div class="upanel-stat">
                <i class="las la-check-circle"></i>
                <strong>{{ $orderStats['completed'] }}</strong>
                <span>Completados</span>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="upanel-card">
            <h3><i class="las la-receipt"></i> Pedidos Recientes</h3>
            @forelse($recentOrders as $order)
            <a href="{{ route('user.order.detail', $order->id) }}" class="order-row" style="text-decoration:none;color:inherit">
                <div class="order-row-left">
                    <i class="las la-bag"></i>
                    <div>
                        <strong>{{ $order->order_no }}</strong>
                        <small>{{ $order->store?->name ?? 'Tienda' }} &middot; {{ $order->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
                <div>
                    <span class="order-badge badge-{{ ($order->status === 'pending') ? 'pending' : ((in_array($order->status, ['confirmed','preparing','on_the_way'])) ? 'active' : (($order->status === 'delivered') ? 'completed' : 'cancelled')) }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
            </a>
            @empty
            <div class="upanel-empty">
                <i class="las la-inbox"></i>
                <p>Aún no tienes pedidos</p>
            </div>
            @endforelse
        </div>

        <!-- Quick Actions -->
        <div class="upanel-card">
            <h3><i class="las la-bolt"></i> Acciones Rápidas</h3>
            <div class="upanel-menu">
                <a href="{{ route('user.profile') }}"><i class="las la-user"></i> Mi Perfil</a>
                <a href="{{ route('user.wallet') }}"><i class="las la-wallet"></i> Billetera</a>
                <a href="{{ route('user.deposit.history') }}"><i class="las la-history"></i> Historial de Pagos</a>
                <a href="{{ route('home') }}"><i class="las la-home"></i> Volver al Inicio</a>
            </div>
        </div>
    </div>
</div>
@endsection
