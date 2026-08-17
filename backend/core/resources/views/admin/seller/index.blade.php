@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table.layout searchPlaceholder="Search sellers" :renderExportButton="false">
                        <x-admin.ui.table>
                            <x-admin.ui.table.header>
                                <tr>
                                    <th>@lang('Seller')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Phone')</th>
                                    <th>@lang('Stores')</th>
                                    <th>Por cobrar a Lizto</th>
                                    <th>@lang('Verified')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </x-admin.ui.table.header>
                            <x-admin.ui.table.body>
                                @forelse($sellers as $seller)
                                    <tr>
                                        <td>
                                            <div class="flex-thumb-wrapper gap-1">
                                                <div class="thumb">
                                                    <img src="{{ getImage(getFilePath('user') . '/' . $seller->avatar, getFilePath('user'), isAvatar: true) }}" width="40">
                                                </div>
                                                <span>{{ __($seller->name) }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $seller->email }}</td>
                                        <td>{{ $seller->phone ?? 'N/A' }}</td>
                                        <td>{{ $seller->stores_count }}</td>
                                        <td><a href="{{ route('admin.seller.detail', $seller->id) }}" class="fw-bold {{ $seller->receivable_balance > 0 ? 'text--success' : '' }}">{{ showAmount($seller->receivable_balance) }}</a></td>
                                        <td>
                                            @if($seller->is_verified)
                                                <span class="badge badge--success">@lang('Verified')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('Unverified')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-admin.other.status_switch :status="$seller->status" :action="route('admin.seller.status', $seller->id)" title="seller" />
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('admin.seller.detail', $seller->id) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="la la-eye"></i> @lang('Detail')
                                                </a>
                                                @if(!$seller->is_verified)
                                                    <a href="{{ route('admin.seller.verification', $seller->id) }}" class="btn btn-sm btn-outline-success">
                                                        <i class="la la-check"></i> @lang('Verify')
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-admin.ui.table.empty_message />
                                @endforelse
                            </x-admin.ui.table.body>
                        </x-admin.ui.table>
                        @if ($sellers->hasPages())
                            <x-admin.ui.table.footer>
                                {{ paginateLinks($sellers) }}
                            </x-admin.ui.table.footer>
                        @endif
                    </x-admin.ui.table.layout>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.seller.index') }}" class="btn btn-sm btn-outline-primary">@lang('All')</a>
        <a href="{{ route('admin.seller.pending') }}" class="btn btn-sm btn-outline-warning">@lang('Pending')</a>
        <a href="{{ route('admin.seller.approved') }}" class="btn btn-sm btn-outline-success">@lang('Approved')</a>
        <a href="{{ route('admin.seller.banned') }}" class="btn btn-sm btn-outline-danger">@lang('Banned')</a>
    </div>
@endpush
