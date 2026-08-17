@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-4">
        <div class="col-xl-4 col-lg-5">
            <x-admin.ui.card>
                <x-admin.ui.card.header>
                    <h5 class="mb-0 card-title">@lang('SOS Details')</h5>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Passenger')</span>
                            <span>{{ @$alert->ride->user->fullname ?? __('N/A') }}
                                @if (@$alert->ride->user->username)
                                    <small class="d-block text-muted">{{ '@' . $alert->ride->user->username }}</small>
                                @endif
                            </span>
                        </li>
                        @if (@$alert->ride->user->mobile)
                            <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                                <span class="fw-bold">@lang('Phone')</span>
                                <span>{{ @$alert->ride->user->dial_code }}{{ $alert->ride->user->mobile }}</span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Driver')</span>
                            <span>{{ @$alert->ride->driver->fullname ?? __('No driver available') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Ride')</span>
                            @if (@$alert->ride)
                                <a href="{{ route('admin.rides.detail', $alert->ride->id) }}">{{ @$alert->ride->uid }}</a>
                            @else
                                <span>@lang('N/A')</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Date')</span>
                            <span>{{ \Carbon\Carbon::parse($alert->created_at)->format('d M, Y h:i A') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Coordinates')</span>
                            <span>{{ $alert->latitude }}, {{ $alert->longitude }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                            <span class="fw-bold">@lang('Status')</span>
                            <span>@php echo $alert->statusBadge @endphp</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold d-block mb-1">@lang('Message')</span>
                            <span>{{ $alert->message ?: __('No message') }}</span>
                        </li>
                    </ul>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <a target="_blank"
                            href="https://www.google.com/maps/search/?api=1&query={{ $alert->latitude }},{{ $alert->longitude }}"
                            class="btn btn--info btn-sm">
                            <i class="las la-external-link-alt"></i> @lang('Open in Google Maps')
                        </a>
                        @if ($alert->status == \App\Constants\Status::ENABLE)
                            <form action="{{ route('admin.sos.resolve', $alert->id) }}" method="POST"
                                onsubmit="return confirm('@lang('Mark this SOS alert as resolved?')')">
                                @csrf
                                <button type="submit" class="btn btn--primary btn-sm">
                                    <i class="las la-check"></i> @lang('Mark as Resolved')
                                </button>
                            </form>
                        @endif
                    </div>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
        <div class="col-xl-8 col-lg-7">
            <x-admin.ui.card>
                <x-admin.ui.card.body>
                    <div id="map" style="height: 70vh;"></div>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back_btn route="{{ route('admin.sos.index') }}" />
@endpush

@push('script')
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&callback=initMap"></script>
    <script>
        const sos = {
            lat: parseFloat("{{ $alert->latitude }}"),
            lng: parseFloat("{{ $alert->longitude }}"),
        };
        const pickup = {
            lat: parseFloat("{{ @$alert->ride->pickup_latitude }}"),
            lng: parseFloat("{{ @$alert->ride->pickup_longitude }}"),
        };
        const dropoff = {
            lat: parseFloat("{{ @$alert->ride->destination_latitude }}"),
            lng: parseFloat("{{ @$alert->ride->destination_longitude }}"),
        };

        function initMap() {
            const map = new google.maps.Map(document.getElementById("map"), {
                center: sos,
                zoom: 15,
            });

            const sosMarker = new google.maps.Marker({
                position: sos,
                map,
                title: "SOS",
                icon: {
                    url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
                },
                animation: google.maps.Animation.BOUNCE,
            });

            const info = new google.maps.InfoWindow({
                content: `<strong>@lang('SOS') - {{ addslashes(@$alert->ride->user->fullname) }}</strong><br>{{ addslashes($alert->message) }}`
            });
            info.open(map, sosMarker);
            sosMarker.addListener("click", () => info.open(map, sosMarker));

            if (!isNaN(pickup.lat) && !isNaN(pickup.lng) && pickup.lat !== 0) {
                new google.maps.Marker({
                    position: pickup,
                    map,
                    label: "P",
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/green-dot.png"
                    }
                });
            }
            if (!isNaN(dropoff.lat) && !isNaN(dropoff.lng) && dropoff.lat !== 0) {
                new google.maps.Marker({
                    position: dropoff,
                    map,
                    label: "D",
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    }
                });
            }
        }
        window.initMap = initMap;
    </script>
@endpush
