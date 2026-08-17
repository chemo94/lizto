@extends('admin.layouts.app')
@section('panel')
<div class="row">
<div class="col-lg-6">
    <div class="card mb-4"><div class="card-header"><h5>Comisión Repartidor</h5></div>
    <div class="card-body">
    <form method="POST" action="{{ route('admin.delivery.commission.update') }}">@csrf
    <div class="mb-3"><label>Tipo</label>
    <select name="courier_commission_type" class="form-select">
        <option value="percent" {{ ($commission->courier_commission_type??'percent')=='percent'?'selected':'' }}>Porcentaje (%)</option>
        <option value="fixed" {{ ($commission->courier_commission_type??'percent')=='fixed'?'selected':'' }}>Monto Fijo (S/)</option>
    </select></div>
    <div class="mb-3"><label>% Comisión Delivery</label>
    <input type="number" step="0.01" name="delivery_percent" class="form-control" value="{{ $commission->delivery_percent ?? 10 }}"></div>
    <div class="mb-3"><label>% Comisión Favor</label>
    <input type="number" step="0.01" name="favor_percent" class="form-control" value="{{ $commission->favor_percent ?? 15 }}"></div>
    <div class="mb-3"><label>Monto Fijo Repartidor</label>
    <input type="number" step="0.01" name="courier_fixed_amount" class="form-control" value="{{ $commission->courier_fixed_amount ?? 0 }}"></div>
    </div></div>
</div>
<div class="col-lg-6">
    <div class="card mb-4"><div class="card-header"><h5>Comisión Tienda</h5></div>
    <div class="card-body">
    <div class="mb-3"><label>Tipo</label>
    <select name="store_commission_type" class="form-select">
        <option value="percent" {{ ($commission->store_commission_type??'percent')=='percent'?'selected':'' }}>Porcentaje (%)</option>
        <option value="fixed" {{ ($commission->store_commission_type??'percent')=='fixed'?'selected':'' }}>Monto Fijo (S/)</option>
    </select></div>
    <div class="mb-3"><label>% Comisión Tienda</label>
    <input type="number" step="0.01" name="store_commission_percent" class="form-control" value="{{ $commission->store_commission_percent ?? 5 }}"></div>
    <div class="mb-3"><label>Monto Fijo Tienda</label>
    <input type="number" step="0.01" name="store_fixed_amount" class="form-control" value="{{ $commission->store_fixed_amount ?? 0 }}"></div>
    <div class="mb-3"><label>Comisión Mínima (S/)</label>
    <input type="number" step="0.01" name="min_commission" class="form-control" value="{{ $commission->min_commission ?? 1 }}"></div>
    <button type="submit" class="btn btn--primary w-100">Guardar Configuración</button>
    </div></div></div>
</div>
</form>
</div>
<div class="row mt-3"><div class="col-12">
    <div class="card"><div class="card-header"><h5>Vista previa de cálculo</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary"><div class="card-body text-center">
                    <h5>Delivery S/ 30.00</h5>
                    <hr>
                    <p>Repartidor ({{ ($commission->courier_commission_type??'percent')=='percent' ? ($commission->delivery_percent??10).'%' : 'S/ '.($commission->courier_fixed_amount??0) }}): 
                        <strong>S/ {{ $commission->courier_commission_type=='fixed' ? number_format($commission->courier_fixed_amount,2) : number_format(30*($commission->delivery_percent??10)/100,2) }}</strong></p>
                    <p>Tienda ({{ ($commission->store_commission_type??'percent')=='percent' ? ($commission->store_commission_percent??5).'%' : 'S/ '.($commission->store_fixed_amount??0) }}): 
                        <strong>S/ {{ $commission->store_commission_type=='fixed' ? number_format($commission->store_fixed_amount,2) : number_format(30*($commission->store_commission_percent??5)/100,2) }}</strong></p>
                    <hr>
                    <p>Ganancia repartidor: <strong class="text-success">S/ {{ $commission->courier_commission_type=='fixed' ? number_format(30-($commission->courier_fixed_amount??0),2) : number_format(30-30*($commission->delivery_percent??10)/100,2) }}</strong></p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning"><div class="card-body text-center">
                    <h5>Favor S/ 25.00</h5>
                    <hr>
                    <p>Repartidor ({{ ($commission->courier_commission_type??'percent')=='percent' ? ($commission->favor_percent??15).'%' : 'S/ '.($commission->courier_fixed_amount??0) }}): 
                        <strong>S/ {{ $commission->courier_commission_type=='fixed' ? number_format($commission->courier_fixed_amount,2) : number_format(25*($commission->favor_percent??15)/100,2) }}</strong></p>
                    <hr>
                    <p>Ganancia repartidor: <strong class="text-success">S/ {{ $commission->courier_commission_type=='fixed' ? number_format(25-($commission->courier_fixed_amount??0),2) : number_format(25-25*($commission->favor_percent??15)/100,2) }}</strong></p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-info"><div class="card-body text-center">
                    <h5>Resumen</h5>
                    <table class="table table-sm">
                        <tr><td>Tipo repartidor:</td><td><strong>{{ ($commission->courier_commission_type??'percent')=='percent' ? 'Porcentaje' : 'Monto Fijo' }}</strong></td></tr>
                        <tr><td>Tipo tienda:</td><td><strong>{{ ($commission->store_commission_type??'percent')=='percent' ? 'Porcentaje' : 'Monto Fijo' }}</strong></td></tr>
                        <tr><td>Comisión mínima:</td><td><strong>S/ {{ $commission->min_commission ?? 1 }}</strong></td></tr>
                    </table>
                </div></div>
            </div>
        </div>
    </div></div>
</div></div>
@endsection
