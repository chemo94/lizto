@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h5>Crear Cupón</h5></div><div class="card-body">
            <form method="POST" action="{{ route('admin.delivery.coupons.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-2"><label>Nombre</label><input type="text" name="name" class="form-control" placeholder="Primer pedido" required></div>
                    <div class="col-md-6 mb-2"><label>Código</label><input type="text" name="code" class="form-control" placeholder="BIENVENIDO10" required></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2"><label>Tipo</label><select name="type" class="form-select"><option value="percentage">Porcentaje %</option><option value="fixed">Monto fijo</option><option value="free_delivery">Delivery gratis</option></select></div>
                    <div class="col-md-4 mb-2"><label>Valor</label><input type="number" name="value" class="form-control" step="0.01" placeholder="10"></div>
                    <div class="col-md-4 mb-2"><label>Pedido mínimo</label><input type="number" name="min_order" class="form-control" step="0.01" value="0"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2"><label>Desc. máximo</label><input type="number" name="max_discount" class="form-control" step="0.01"></div>
                    <div class="col-md-4 mb-2"><label>Límite usos</label><input type="number" name="usage_limit" class="form-control"></div>
                    <div class="col-md-4 mb-2"><label>Por usuario</label><input type="number" name="per_user_limit" class="form-control" value="1"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2"><label>Válido desde</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                    <div class="col-md-6 mb-2"><label>Válido hasta</label><input type="datetime-local" name="expires_at" class="form-control"></div>
                </div>
                <div class="mb-2"><label>Descripción</label><input type="text" name="description" class="form-control"></div>
                <button type="submit" class="btn btn--primary mt-2">Crear Cupón</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h5>Cupones ({{ $coupons->count() }})</h5></div><div class="card-body p-0">
            <div class="table-responsive"><table class="table"><thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Usos</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
                @forelse($coupons as $c)
                <tr>
                    <td><code>{{ $c->code }}</code></td><td>{{ $c->name }}</td>
                    <td>
                        @if($c->type === 'percentage')
                            @lang('Porcentaje')
                        @elseif($c->type === 'fixed')
                            @lang('Monto fijo')
                        @else
                            @lang('Envío gratis')
                        @endif
                    </td>
                    <td>
                        @if($c->type === 'percentage')
                            {{ showAmount($c->value) }}%
                        @elseif($c->type === 'fixed')
                            S/ {{ showAmount($c->value) }}
                        @else
                            @lang('Gratis')
                        @endif
                    </td>
                    <td>{{ $c->usage_count }}/{{ $c->usage_limit ?: '∞' }}</td>
                    <td><span class="badge {{ $c->status ? 'badge--success' : 'badge--danger' }}">{{ $c->status ? 'Activo' : 'Inactivo' }}</span></td>
                    <td><form method="POST" action="{{ route('admin.delivery.coupons.delete', $c->id) }}" onsubmit="return confirm('¿Eliminar?')">@csrf<button class="btn btn-sm btn-outline-danger"><i class="las la-trash"></i></button></form></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">Sin cupones</td></tr>
                @endforelse
            </tbody></table></div>
        </div></div>

        @if($usages->count())
        <div class="card mt-3"><div class="card-header"><h5>Usos Recientes</h5></div><div class="card-body p-0">
            <div class="table-responsive"><table class="table"><thead><tr><th>Usuario</th><th>Cupón</th><th>Pedido</th><th>Fecha</th></tr></thead><tbody>
                @foreach($usages as $u)
                <tr><td>{{ $u->user?->fullname }}</td><td><code>{{ $u->coupon?->code }}</code></td><td>#{{ $u->order_id }}</td><td>{{ $u->created_at->format('d/m H:i') }}</td></tr>
                @endforeach
            </tbody></table></div>
        </div>
        @endif
    </div>
</div>
@endsection
