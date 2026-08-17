@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <!-- Form create category -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5>Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.delivery.category.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Slug</label>
                        <input type="text" name="slug" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Imagen de Categoría (200x200 px)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="status" class="form-check-input" id="create-status" checked>
                            <label class="form-check-label" for="create-status">Activo</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary w-100">Crear</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories table -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5>{{ $pageTitle }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--md">
                    <table class="table table--light">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Subcategorías</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $c)
                            <tr>
                                <td>
                                    <img src="{{ getImage('assets/images/general_category/'.$c->image) }}" height="40" width="40" class="rounded">
                                </td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->slug }}</td>
                                <td>{{ $c->sub_categories_count }}</td>
                                <td>
                                    <span class="badge badge--{{ $c->status?'success':'danger' }}">{{ $c->status?'Activo':'Inactivo' }}</span>
                                </td>
                                <td>
                                    <div class="button--group">
                                        <a href="{{ route('admin.delivery.subcategories',$c->id) }}" class="btn btn-sm btn-outline--primary"><i class="la la-eye"></i> Subs</a>
                                        <button class="btn btn-sm btn-outline--info editBtn" data-id="{{ $c->id }}" data-name="{{ $c->name }}" data-slug="{{ $c->slug }}" data-status="{{ $c->status }}" data-image="{{ getImage('assets/images/general_category/'.$c->image) }}"><i class="la la-pen"></i></button>
                                        <button class="btn btn-sm btn-outline--danger deleteBtn" data-id="{{ $c->id }}"><i class="la la-trash"></i></button>
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
                <h5 class="modal-title">Editar Categoría</h5>
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
                        <label>Slug</label>
                        <input type="text" name="slug" id="edit-slug" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Imagen de Categoría (200x200 px)</label>
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
                    <p>¿Está seguro de que desea eliminar esta categoría? Se eliminarán todas sus subcategorías asociadas.</p>
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
            var slug = $(this).data('slug');
            var status = $(this).data('status');
            var image = $(this).data('image');

            modal.find('#editForm').attr('action', `{{ route('admin.delivery.category.update', '') }}/${id}`);
            modal.find('#edit-name').val(name);
            modal.find('#edit-slug').val(slug);
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
            modal.find('#deleteForm').attr('action', `{{ route('admin.delivery.category.delete', '') }}/${id}`);
            modal.modal('show');
        });
    })(jQuery);
</script>
@endpush
