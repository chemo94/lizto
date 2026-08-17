@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table.layout searchPlaceholder="Search banners" :renderExportButton="false">
                        <x-admin.ui.table>
                            <x-admin.ui.table.header>
                                <tr>
                                    <th>@lang('Image')</th>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Sort Order')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </x-admin.ui.table.header>
                            <x-admin.ui.table.body>
                                @forelse($banners as $banner)
                                    <tr>
                                        <td>
                                            <div class="flex-thumb-wrapper gap-1">
                                                <div class="thumb">
                                                    <img src="{{ imageGet('banner', $banner->image) }}" width="80">
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ __($banner->title) }}</td>
                                        <td>
                                            @if($banner->type == 'delivery')
                                                <span class="badge badge--success">@lang('Delivery')</span>
                                            @else
                                                <span class="badge badge--primary">@lang('Ride') / @lang('Taxi')</span>
                                            @endif
                                        </td>
                                        <td>{{ $banner->sort_order }}</td>
                                        <td>
                                            <x-admin.other.status_switch :status="$banner->status" :action="route('admin.banner.status', $banner->id)" title="banner" />
                                        </td>
                                        <td>
                                            <x-admin.ui.btn.edit tag="button" :data-image="imageGet('banner', $banner->image)" :data-resource="$banner" />
                                        </td>
                                    </tr>
                                @empty
                                    <x-admin.ui.table.empty_message />
                                @endforelse
                            </x-admin.ui.table.body>
                        </x-admin.ui.table>
                        @if ($banners->hasPages())
                            <x-admin.ui.table.footer>
                                {{ paginateLinks($banners) }}
                            </x-admin.ui.table.footer>
                        @endif
                    </x-admin.ui.table.layout>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>

    <x-admin.ui.modal id="modal">
        <x-admin.ui.modal.header>
            <h4 class="modal-title"></h4>
            <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close">
                <i class="las la-times"></i>
            </button>
        </x-admin.ui.modal.header>
        <x-admin.ui.modal.body>
            <form action="{{ route('admin.banner.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>@lang('Title')</label>
                    <input class="form-control" name="title" type="text" value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label>@lang('Type')</label>
                    <select class="form-control" name="type" required>
                        <option value="taxi">@lang('Ride') / @lang('Taxi')</option>
                        <option value="delivery">@lang('Delivery')</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('Link')</label>
                    <input class="form-control" name="link" type="url" value="{{ old('link') }}" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>@lang('Sort Order')</label>
                    <input class="form-control" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}">
                </div>
                <div class="form-group">
                    <label>@lang('Image') <span class="text-danger">*</span></label>
                    <x-image-uploader type="banner" />
                </div>
                <div class="form-group">
                    <x-admin.ui.btn.modal />
                </div>
            </form>
        </x-admin.ui.modal.body>
    </x-admin.ui.modal>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            const $modal = $("#modal");

            $(".edit-btn").on('click', function(e) {
                const data = $(this).data('resource');
                const imagePath = $(this).data('image');
                const action = "{{ route('admin.banner.update', ':id') }}";

                $("input[name='title']").val(data.title);
                $("select[name='type']").val(data.type || 'taxi');
                $("input[name='link']").val(data.link);
                $("input[name='sort_order']").val(data.sort_order);
                $modal.find(".modal-title").text("@lang('Edit Banner')");
                $modal.find(".image-upload img").attr('src', imagePath);
                $modal.find(".image-upload [type=file]").attr('required', false);
                $modal.find('form').attr('action', action.replace(':id', data.id));
                $modal.modal("show");
            });

            $(".add-btn").on('click', function(e) {
                const action = "{{ route('admin.banner.store') }}";
                $modal.find(".modal-title").text("@lang('Add Banner')");
                $modal.find('form').trigger('reset');
                $("select[name='type']").val('taxi');
                $modal.find('form').attr('action', action);
                $modal.find(".image-upload img").attr('src', "{{ asset('assets/images/drag-and-drop.png') }}");
                $modal.find(".image-upload [type=file]").attr('required', true);
                $modal.modal("show");
            });
        })(jQuery);
    </script>
@endpush

@push('modal')
    <x-confirmation-modal />
@endpush

@push('breadcrumb-plugins')
    <x-admin.ui.btn.add tag="button" />
@endpush

@push('style')
    <style>
        .flex-thumb-wrapper .thumb img {
            border-radius: 5px;
        }
    </style>
@endpush
