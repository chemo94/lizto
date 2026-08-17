@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Nombre de la Flota</th>
                                    <th>Dueño / Admin</th>
                                    <th>Código de Invitación</th>
                                    <th>Email</th>
                                    <th>Comisión Lizto</th>
                                    <th>Comisión Conductores</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fleets as $item)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $item->name }}</span>
                                        </td>
                                        <td>
                                            @if($item->owner)
                                                <span class="d-block">{{ $item->owner->firstname }} {{ $item->owner->lastname }}</span>
                                                <span class="small text-muted">{{ $item->owner->username }}</span>
                                            @else
                                                <span class="text--danger">Sin Dueño</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge--primary">{{ $item->invite_code }}</span>
                                        </td>
                                        <td>
                                            <span>{{ $item->email }}</span>
                                        </td>
                                        <td>
                                            <span>{{ $item->lizto_commission_rate }}%</span>
                                        </td>
                                        <td>
                                            <span>{{ $item->driver_commission_rate }}%</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.fleet.detail', $item->id) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="la la-desktop"></i> Detalles
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">No hay flotas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($fleets->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($fleets) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
