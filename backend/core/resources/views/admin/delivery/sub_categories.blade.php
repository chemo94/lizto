@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <!-- Form create subcategory -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5>Nueva Subcategoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.delivery.subcategory.store',$category->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Orden</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label>Imagen de Subcategoría (200x200 px)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn--primary w-100">Crear</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Subcategories list -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5>Subcategorías de {{ $category->name }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--md">
                    <table class="table table--light">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subs as $s)
                            <tr>
                                <td>
                                    <img src="{{ getImage(getFilePath('sub_category').'/'.$s->image) }}" height="40" width="40" class="rounded">
                                </td>
                                <td>{{ $s->name }}</td>
                                <td>{{ $s->sort_order }}</td>
                                <td>
                                    <span class="badge badge--{{ $s->status?'success':'danger' }}">{{ $s->status?'Activo':'Inactivo' }}</span>
                                </td>
                                <td>
                                    <div class="button--group">
                                        <button class="btn btn-sm btn-outline--info editBtn" data-id="{{ $s->id }}" data-name="{{ $s->name }}" data-sort_order="{{ $s->sort_order }}" data-status="{{ $s->status }}" data-image="{{ getImage(getFilePath('sub_category').'/'.$s->image) }}"><i class="la la-pen"></i></button>
                                        <button class="btn btn-sm btn-outline--danger deleteBtn" data-id="{{ $s->id }}"><i class="la la-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Subcategoría</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form method="POST" action="" id="editForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Orden</label>
                        <input type="number" name="sort_order" id="edit-sort" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label>Imagen de Subcategoría (200x200 px)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="mt-2 text-center">
                            <img src="" id="edit-image-preview" height="80" class="rounded">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="status" class="form-check-input" id="edit-status">
                            <label class="form-check-label" for="edit-status">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn--primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form method="POST" action="" id="deleteForm">
                @csrf
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar esta subcategoría?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn--danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function($){
        "use strict";
        $('.editBtn').on('click', function() {
            var modal = $('#editModal');
            var id = $(this).data('id');
            var name = $(this).data('name');
            var sort = $(this).data('sort_order');
            var status = $(this).data('status');
            var image = $(this).data('image');

            modal.find('#editForm').attr('action', `{{ route('admin.delivery.subcategory.update', '') }}/${id}`);
            modal.find('#edit-name').val(name);
            modal.find('#edit-sort').val(sort);
            modal.find('#edit-image-preview').attr('src', image);

            if(status == 1) {
                modal.find('#edit-status').prop('checked', true);
            } else {
                modal.find('#edit-status').prop('checked', false);
            }
            modal.modal('show');
        });

        $('.deleteBtn').on('click', function() {
            var modal = $('#deleteModal');
            var id = $(this).data('id');
            modal.find('#deleteForm').attr('action', `{{ route('admin.delivery.subcategory.delete', '') }}/${id}`);
            modal.modal('show');
        });
    })(jQuery);
</script>
@endpush
