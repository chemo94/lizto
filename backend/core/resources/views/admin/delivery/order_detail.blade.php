@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5>Pedido #{{ $order->order_no }}</h5></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Cliente:</strong> {{ $order->contact_name ?: $order->user?->fullname }}<br><strong>Tel:</strong> {{ $order->contact_phone ?: $order->user?->mobile }}</div>
                    <div class="col-md-6"><strong>Tienda:</strong> {{ $order->store?->name }}<br><strong>Subtotal:</strong> S/ {{ number_format($order->subtotal, 2) }} | <strong>Delivery:</strong> S/ {{ number_format($order->delivery_fee, 2) }} | <strong>Propina:</strong> S/ {{ number_format($order->tip, 2) }}</div>
                </div>
                <h6>Productos</h6>
                <table class="table table--light table-sm">
                    <thead><tr><th>Producto</th><th>Var/Addons</th><th>Cant</th><th>Precio</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>
                                @if($item->variation)
                                    <small class="text--primary">{{ $item->variation->variation_name }}@if((float)$item->variation->variation_price > 0) (S/ {{ number_format($item->variation->variation_price, 2) }})@endif</small><br>
                                @endif
                                @foreach($item->addons as $a) <small class="text--muted">+ {{ $a->addon_name }}</small><br> @endforeach
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>S/ {{ number_format($item->unit_price, 2) }}</td>
                            <td>S/ {{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="4" class="text-end">Total:</th><th>S/ {{ number_format($order->total, 2) }}</th></tr></tfoot>
                </table>
                <p><strong>Dirección:</strong> {{ $order->delivery_address }} | <strong>Contacto:</strong> {{ $order->contact_name }} - {{ $order->contact_phone }}</p>
                @if($order->notes)<p><strong>Notas:</strong> {{ $order->notes }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5>Acciones</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.delivery.order.status', $order->id) }}">
                    @csrf
                    <label>Estado</label>
                    <select name="status" class="form-select mb-2">
                        @foreach(['pending'=>'Pendiente','confirmed'=>'Confirmado','preparing'=>'Preparando','ready'=>'Listo','on_way'=>'En camino','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $k=>$v)
                            <option value="{{ $k }}" {{ $order->status==$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn--primary w-100 mb-3">Actualizar Estado</button>
                </form>
                <form method="POST" action="{{ route('admin.delivery.order.assign', $order->id) }}">
                    @csrf
                    <label>Asignar Repartidor</label>
                    <select name="driver_id" class="form-select mb-2">
                        <option value="">Seleccionar...</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" {{ $order->driver_id==$d->id?'selected':'' }}>{{ $d->fullname }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn--success w-100">Asignar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
