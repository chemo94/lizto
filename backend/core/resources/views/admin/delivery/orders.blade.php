@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $pageTitle }}</h5>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar #pedido..." value="{{ request('search') }}">
                    <select name="status" class="form-select form-select-sm" style="width:auto">
                        <option value="">Todos</option>
                        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pendiente</option>
                        <option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>Confirmado</option>
                        <option value="preparing" {{ request('status')=='preparing'?'selected':'' }}>Preparando</option>
                        <option value="ready" {{ request('status')=='ready'?'selected':'' }}>Listo</option>
                        <option value="on_way" {{ request('status')=='on_way'?'selected':'' }}>En camino</option>
                        <option value="delivered" {{ request('status')=='delivered'?'selected':'' }}>Entregado</option>
                        <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelado</option>
                    </select>
                    <button type="submit" class="btn btn--primary btn-sm"><i class="las la-search"></i></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--md">
                    <table class="table table--light">
                        <thead>
                            <tr>
                                <th>#Pedido</th><th>Cliente</th><th>Tienda</th><th>Total</th><th>Repartidor</th><th>Estado</th><th>Fecha</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td><strong>{{ $order->order_no }}</strong></td>
                                <td>{{ $order->user?->fullname ?? 'N/A' }}</td>
                                <td>{{ $order->store?->name ?? 'N/A' }}</td>
                                <td>S/ {{ number_format($order->total, 2) }}</td>
                                <td>{{ $order->driver?->fullname ?? 'Sin asignar' }}</td>
                                <td>@php $colors=['pending'=>'warning','confirmed'=>'primary','preparing'=>'info','ready'=>'success','on_way'=>'dark','delivered'=>'success','cancelled'=>'danger']; @endphp
                                    <span class="badge badge--{{ $colors[$order->status] ?? 'secondary' }}">{{ $order->status }}</span>
                                </td>
                                <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                <td><a href="{{ route('admin.delivery.order.detail', $order->id) }}" class="btn btn-sm btn-outline--primary"><i class="las la-eye"></i></a></td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center">No hay pedidos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
