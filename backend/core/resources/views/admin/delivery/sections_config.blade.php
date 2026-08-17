@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <form method="POST" action="{{ route('admin.delivery.sections.config.update') }}">
            @csrf
            <div class="card b-radius--10 ">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0">Configuración de Secciones en Pantalla de Inicio</h5>
                    <small class="text-white-50">Gestiona qué secciones y en qué orden aparecen en el home de delivery</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Sección Base</th>
                                    <th>Título Personalizado</th>
                                    <th>Subtítulo Personalizado</th>
                                    <th>Habilitado</th>
                                    <th>Orden</th>
                                    <th>Tipo de Contenido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sections as $index => $sec)
                                <tr>
                                    <td>
                                        <span class="font-weight-bold text--primary">{{ ucwords(str_replace('_', ' ', $sec['key'])) }}</span>
                                        <input type="hidden" name="sections[{{ $index }}][key]" value="{{ $sec['key'] }}">
                                    </td>
                                    <td>
                                        <input type="text" name="sections[{{ $index }}][title]" class="form-control form-control-sm" value="{{ $sec['title'] }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="sections[{{ $index }}][subtitle]" class="form-control form-control-sm" value="{{ $sec['subtitle'] ?? '' }}">
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input type="checkbox" name="sections[{{ $index }}][is_enabled]" class="form-check-input" id="switch-{{ $sec['key'] }}" value="1" {{ ($sec['is_enabled'] ?? 0) == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="switch-{{ $sec['key'] }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="sections[{{ $index }}][sort_order]" class="form-control form-control-sm text-center" style="width: 70px; margin: 0 auto;" value="{{ $sec['sort_order'] ?? 0 }}" required min="1">
                                    </td>
                                    <td>
                                        @if(in_array($sec['key'], ['populares_cerca_ti', 'marcas_descuento', 'recomendados_ti']))
                                            <span class="badge badge--success">Tiendas</span>
                                        @else
                                            <span class="badge badge--primary">Productos</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <button type="submit" class="btn btn--primary h-45 w-100">Guardar Configuración de Secciones</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
