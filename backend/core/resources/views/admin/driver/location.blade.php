@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.header>
                    <div class="d-flex justify-content-between gap-2 flex-wrap align-items-center">
                        <div>
                            <h5 class="mb-0 card-title">@lang('Driver Location')</h5>
                            <small class="text-muted">
                                {{ $driver->fullname }} &mdash;
                                @php $svcType = $driver->service_type ?? 'ride'; @endphp
                                <span class="badge badge--{{ $svcType === 'ride' ? 'info' : ($svcType === 'both' ? 'primary' : 'warning') }}">
                                    {{ $svcType === 'ride' ? __('Driver') : ($svcType === 'both' ? __('Driver + Delivery') : __('Delivery')) }}
                                </span>
                                <span class="ms-1 badge badge--{{ $driver->online_status ? 'success' : 'secondary' }}">
                                    {{ $driver->online_status ? __('Online') : __('Offline') }}
                                </span>
                            </small>
                        </div>
                        <div class="text-end">
                            @if ($driver->last_location_fetch_at)
                                <span class="d-block text-muted" style="font-size:12px;">@lang('Last Location Fetch At')</span>
                                <div>
                                    <span>{{ showDateTime($driver->last_location_fetch_at) }}</span>
                                    <span class="text--danger">({{ diffForHumans($driver->last_location_fetch_at) }})</span>
                                </div>
                            @else
                                <span class="badge badge--warning">@lang('Location never reported')</span>
                            @endif
                        </div>
                    </div>
                </x-admin.ui.card.header>
                <x-admin.ui.card.body>
                    @php
                        $hasLocation = !empty($driver->current_lat) && !empty($driver->current_lot)
                            && is_numeric($driver->current_lat) && is_numeric($driver->current_lot);
                    @endphp

                    @if (!$hasLocation)
                        {{-- No location available ──────────────────────────── --}}
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-3"
                             style="min-height:260px; background:#f8f9fa; border-radius:10px; border:1.5px dashed #dee2e6;">
                            <img src="{{ asset('assets/images/empty_box.png') }}" style="width:90px;opacity:.6;" alt="">
                            <h5 class="text--warning mb-0">@lang('Driver location not available at the moment')</h5>
                            @if ($svcType === 'delivery' || $svcType === 'both')
                                <p class="text-muted text-center mb-0" style="max-width:440px; font-size:13px;">
                                    @lang('This driver has the')
                                    <strong>{{ __($svcType === 'delivery' ? 'Delivery' : 'Delivery + Ride') }}</strong>
                                    @lang('service type. Location is shared by the mobile app while the driver is active on a delivery order.')
                                    @lang('Make sure the driver is online and has granted location permissions in the app.')
                                </p>
                            @else
                                <p class="text-muted mb-0" style="font-size:13px;">
                                    @lang('The driver has not shared their location yet. They must be online in the app.')
                                </p>
                            @endif
                            <a href="{{ route('admin.driver.detail', $driver->id) }}" class="btn btn--primary btn-sm mt-1">
                                <i class="las la-arrow-left me-1"></i> @lang('Back to Driver')
                            </a>
                        </div>
                    @else
                        {{-- Map ────────────────────────────────────────────── --}}
                        <div class="form-group mb-0">
                            <div id="map" style="height:55vh; border-radius:8px; overflow:hidden;"></div>
                        </div>
                        <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:13px; color:#555;">
                            <span><i class="las la-map-marker-alt text--primary"></i>
                                <strong>Lat:</strong> {{ $driver->current_lat }}
                            </span>
                            <span><i class="las la-map-marker-alt text--primary"></i>
                                <strong>Lng:</strong> {{ $driver->current_lot }}
                            </span>
                        </div>
                    @endif
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>
@endsection


@push('breadcrumb-plugins')
    <x-back_btn route="{{ route('admin.driver.detail', $driver->id) }}" />
@endpush

@if (!empty($driver->current_lat) && !empty($driver->current_lot))
@push('script-lib')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=drawing,places&v=3.45.8"></script>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {

            let lat = parseFloat("{{ $driver->current_lat }}");
            let lng = parseFloat("{{ $driver->current_lot }}");
            let map, driverMarker, infoWindow;

            function initMap() {
                if (isNaN(lat) || isNaN(lng)) return;

                const location = { lat: lat, lng: lng };

                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 15,
                    center: location,
                    mapTypeControl: true,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                @php $svcType = $driver->service_type ?? 'ride'; @endphp
                const iconConfig = {
                    url: "{{ asset('assets/images/custom/mototaxi_marker.png') }}",
                    scaledSize: new google.maps.Size(40, 40),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(20, {{ ($svcType === 'delivery' || $svcType === 'both') ? '40' : '20' }})
                };

                driverMarker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: "{{ $driver->fullname }}",
                    icon: iconConfig
                });

                infoWindow = new google.maps.InfoWindow({
                    content: `<div style="min-width:160px;">
                        <strong>{{ $driver->fullname }}</strong><br>
                        <span style="font-size:12px;color:#555;">@lang('Service'): {{ ucfirst($svcType) }}</span><br>
                        <span id="last-update-time" style="font-size:11px;color:#888;">
                            {{ $driver->last_location_fetch_at ? 'Updated: ' . showDateTime($driver->last_location_fetch_at) : 'No timestamp' }}
                        </span>
                    </div>`
                });

                infoWindow.open(map, driverMarker);

                // Start polling for live coordinates
                setInterval(fetchLiveLocation, 4000);
            }

            async function fetchLiveLocation() {
                try {
                    const response = await fetch(`{{ route('admin.driver.live.location', $driver->id) }}`);
                    const data = await response.json();
                    console.log("📡 Polling driver location:", data);
                    if (data.success && data.latitude && data.longitude) {
                        const newLat = parseFloat(data.latitude);
                        const newLng = parseFloat(data.longitude);
                        if (!isNaN(newLat) && !isNaN(newLng)) {
                            const newPos = { lat: newLat, lng: newLng };
                            driverMarker.setPosition(newPos);
                            map.panTo(newPos);

                            if (data.last_location_fetch_at) {
                                $('#last-update-time').text('Updated: ' + data.last_location_fetch_at);
                            }
                        }
                    }
                } catch (err) {
                    console.error("Error fetching driver location:", err);
                }
            }

            window.addEventListener("load", initMap);

        })(jQuery);
    </script>
@endpush
@endif

@push('style')
    <style>
        #map { width: 100%; }
    </style>
@endpush
