@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.header>
                    <div class="d-flex justify-content-between gap-2 flex-wrap align-items-center">
                        <h5 class="mb-0 card-title">@lang('SOS Alerts')</h5>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('admin.sos.index') }}"
                                class="btn btn-sm {{ request()->get('status') === null ? 'btn--primary' : 'btn-outline--primary' }}">@lang('All')</a>
                            <a href="{{ route('admin.sos.index', ['status' => \App\Constants\Status::ENABLE]) }}"
                                class="btn btn-sm {{ request()->get('status') == \App\Constants\Status::ENABLE ? 'btn--danger' : 'btn-outline--danger' }}">@lang('Pending')</a>
                            <a href="{{ route('admin.sos.index', ['status' => \App\Constants\Status::DISABLE]) }}"
                                class="btn btn-sm {{ request()->get('status') !== null && request()->get('status') == \App\Constants\Status::DISABLE ? 'btn--success' : 'btn-outline--success' }}">@lang('Resolved')</a>
                        </div>
                    </div>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table>
                        <x-admin.ui.table.header>
                            <tr>
                                <th>@lang('Passenger')</th>
                                <th>@lang('Driver')</th>
                                <th>@lang('Ride')</th>
                                <th>@lang('Message')</th>
                                <th>@lang('Date')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </x-admin.ui.table.header>
                        <x-admin.ui.table.body>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td><x-admin.other.user_info :user="@$alert->ride->user" /></td>
                                    <td>
                                        @if (@$alert->ride->driver)
                                            <x-admin.other.driver_info :driver="$alert->ride->driver" />
                                        @else
                                            <span>@lang('No driver available')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (@$alert->ride)
                                            <a href="{{ route('admin.rides.detail', $alert->ride->id) }}">
                                                {{ @$alert->ride->uid }}
                                            </a>
                                        @else
                                            <span>@lang('N/A')</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit(@$alert->message, 40) ?: '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($alert->created_at)->format('d M, Y h:i A') }}</td>
                                    <td>@php echo $alert->statusBadge @endphp</td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="{{ route('admin.sos.location', $alert->id) }}"
                                                class="btn btn-sm btn--success">
                                                <i class="las la-map-marker-alt"></i> @lang('Location')
                                            </a>
                                            @if ($alert->status == \App\Constants\Status::ENABLE)
                                                <form action="{{ route('admin.sos.resolve', $alert->id) }}" method="POST"
                                                    onsubmit="return confirm('@lang('Mark this SOS alert as resolved?')')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn--primary">
                                                        <i class="las la-check"></i> @lang('Resolve')
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-admin.ui.table.empty_message />
                            @endforelse
                        </x-admin.ui.table.body>
                    </x-admin.ui.table>
                    @if ($alerts->hasPages())
                        <x-admin.ui.table.footer>
                            {{ paginateLinks($alerts) }}
                        </x-admin.ui.table.footer>
                    @endif
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>
@endsection
