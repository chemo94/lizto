@extends('admin.layouts.app')
@section('panel')
<div class="row justify-content-center">
<div class="col-12">
<x-admin.ui.card>
<x-admin.ui.card.body>
<form action="{{ route('admin.zone.save', @$zone->id ?? 0) }}" method="POST">
@csrf
<div class="form-group">
<label>@lang('Name')</label>
<input class="form-control" name="name" type="text" value="{{ old('name', @$zone->name) }}" required>
</div>
<div class="form-group">
<label>@lang('Country')</label>
<select name="country" class="form-control select2" required @readonly(@$zone)>
@foreach (gs('operating_country') ?? [] as $k => $country)
<option value="{{ $k }}" @selected($k == @$zone->country)>
{{ __($country->country) }}
</option>
@endforeach
</select>
</div>
<div class="form-group">
<label>@lang('Select Area')</label>

{{-- Search Box --}}
<div class="zone-search-wrapper mb-2">
    <div class="zone-search-inner">
        <i class="las la-search zone-search-icon"></i>
        <input
            id="searchBox"
            type="text"
            class="zone-search-input"
            placeholder="@lang('Search city, neighborhood or address...')"
            autocomplete="off"
        >
        <button type="button" id="searchBtn" class="zone-search-btn">
            @lang('Go')
        </button>
    </div>
</div>

{{-- Instructions bar --}}
<div id="instructions" class="zone-instructions mb-2">
    <span><i class="las la-info-circle"></i> @lang('Search a location, then click on the map to draw the polygon. Double-click to close it.')</span>
    <span class="ms-auto d-flex gap-1">
        <button type="button" id="undoBtn" class="btn btn-sm btn-outline-warning">
            <i class="las la-undo"></i> @lang('Undo')
        </button>
        <button type="button" id="clearBtn" class="btn btn-sm btn-outline-danger">
            <i class="las la-trash"></i> @lang('Clear')
        </button>
    </span>
</div>

<textarea class="d-none" id="coordinates" name="coordinates"></textarea>

{{-- Map --}}
<div class="zone-map-wrapper">
    <div id="map"></div>
    <div id="coordsCount" class="zone-coords-badge d-none">
        <i class="las la-map-pin"></i> <span id="coordsNum">0</span> @lang('pts')
    </div>
</div>
</div>
<x-admin.ui.btn.submit />
</form>
</x-admin.ui.card.body>
</x-admin.ui.card>
</div>
</div>
@endsection

@push('breadcrumb-plugins')
<x-back_btn route="{{ route('admin.zone.index') }}" />
@endpush

@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initZoneMap" async defer></script>
@endpush

@push('script')
<script>
"use strict";

var gMap, gPoly, gPts = [], gMks = [], gDrawing = false;
var gAutocomplete, gSearchMarker;

/* ── helpers ─────────────────────────────────────── */
function setCoords() {
    var c = gPts.map(function(p){ return { lat: p.lat(), lng: p.lng() }; });
    document.getElementById('coordinates').value = c.length ? JSON.stringify(c) : '';
    var badge = document.getElementById('coordsCount');
    var num   = document.getElementById('coordsNum');
    if (c.length) { badge.classList.remove('d-none'); num.textContent = c.length; }
    else          { badge.classList.add('d-none'); }
}

function attachPolyListeners(poly) {
    poly.getPath().addListener('set_at',    function(){ gPts = poly.getPath().getArray(); setCoords(); });
    poly.getPath().addListener('insert_at', function(){ gPts = poly.getPath().getArray(); setCoords(); });
    poly.getPath().addListener('remove_at', function(){ gPts = poly.getPath().getArray(); setCoords(); });
}

function makePoly(pts) {
    return new google.maps.Polygon({
        paths: pts,
        strokeColor:   '#0e7cf1',
        strokeOpacity: 0.9,
        strokeWeight:  2,
        fillColor:     '#16a34a',
        fillOpacity:   0.18,
        editable:      true
    });
}

/* ── map init (called by Google Maps callback) ───── */
function initZoneMap() {

    gMap = new google.maps.Map(document.getElementById('map'), {
        zoom: 6,
        center: { lat: -6.485, lng: -76.359 },
        mapTypeControl:      true,
        streetViewControl:   false,
        fullscreenControl:   true,
        zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_CENTER
        }
    });

    /* ── Places Autocomplete ── */
    gAutocomplete = new google.maps.places.Autocomplete(
        document.getElementById('searchBox'),
        { fields: ['geometry', 'name', 'formatted_address'] }
    );
    gAutocomplete.bindTo('bounds', gMap);

    gAutocomplete.addListener('place_changed', function() {
        var place = gAutocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) return;
        goToPlace(place);
    });

    /* "Go" button → geocode the text if no autocomplete selection */
    document.getElementById('searchBtn').addEventListener('click', function() {
        var val = document.getElementById('searchBox').value.trim();
        if (!val) return;
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: val }, function(results, status) {
            if (status === 'OK' && results[0]) {
                goToPlace(results[0]);
            }
        });
    });

    document.getElementById('searchBox').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchBtn').click();
        }
    });

    /* ── Load existing polygon (edit mode) ── */
    @php $coordsData = is_string($coordinates) ? json_decode($coordinates, true) : ($coordinates ?? []); @endphp
    var raw = @json($coordsData);
    if (raw && raw.length) {
        var coords = raw.map(function(p) {
            var lat = p.lat  != null ? parseFloat(p.lat)  : null;
            var lng = p.lng  != null ? parseFloat(p.lng)  :
                     (p.lang != null ? parseFloat(p.lang) : null);
            if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
                return { lat: lat, lng: lng };
            }
            if (Array.isArray(p) && p.length >= 2) return { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
            return null;
        }).filter(function(p){ return p && !isNaN(p.lat) && !isNaN(p.lng); });

        if (coords.length) {
            gPts  = coords.map(function(c){ return new google.maps.LatLng(c.lat, c.lng); });
            gPoly = makePoly(gPts);
            gPoly.setMap(gMap);
            attachPolyListeners(gPoly);
            var b = new google.maps.LatLngBounds();
            coords.forEach(function(c){ b.extend(new google.maps.LatLng(c.lat, c.lng)); });
            gMap.fitBounds(b);
            setCoords();
        }
    }

    /* ── Map click → draw polygon ── */
    gMap.addListener('click', function(e) {
        if (!gDrawing) {
            gDrawing = true;
            if (gPoly) { gPoly.setMap(null); gPoly = null; }
            gPts = [];
            gMks.forEach(function(m){ m.setMap(null); });
            gMks = [];
        }
        var mk = new google.maps.Marker({
            position: e.latLng,
            map: gMap,
            draggable: true,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 6,
                fillColor: '#0e7cf1',
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2
            }
        });
        mk.addListener('dragend', function(){
            gPts = gMks.map(function(m){ return m.getPosition(); });
            if (gPoly) gPoly.setPath(gPts);
            setCoords();
        });
        gMks.push(mk);
        gPts.push(e.latLng);
        if (!gPoly) {
            gPoly = makePoly(gPts);
            gPoly.setMap(gMap);
        }
        gPoly.setPath(gPts);
        setCoords();
    });

    /* ── Double-click → close polygon ── */
    gMap.addListener('dblclick', function(e) {
        e.stop();
        if (gDrawing && gPts.length >= 3) {
            gDrawing = false;
            gPoly.setMap(null);
            gPoly = makePoly(gPts);
            gPoly.setMap(gMap);
            attachPolyListeners(gPoly);
            gMks.forEach(function(m){ m.setMap(null); });
            gMks = [];
            setCoords();
        }
    });

    /* ── Undo ── */
    document.getElementById('undoBtn').addEventListener('click', function() {
        if (!gMks.length) return;
        var m = gMks.pop();
        m.setMap(null);
        gPts = gMks.map(function(x){ return x.getPosition(); });
        if (gPoly) {
            if (gPts.length) gPoly.setPath(gPts);
            else { gPoly.setMap(null); gPoly = null; }
        }
        setCoords();
    });

    /* ── Clear ── */
    document.getElementById('clearBtn').addEventListener('click', function() {
        gMks.forEach(function(m){ m.setMap(null); });
        gMks = []; gPts = [];
        if (gPoly) { gPoly.setMap(null); gPoly = null; }
        if (gSearchMarker) { gSearchMarker.setMap(null); gSearchMarker = null; }
        document.getElementById('coordinates').value = '';
        document.getElementById('coordsCount').classList.add('d-none');
        gDrawing = false;
    });
}

/* ── Pan map to place ── */
function goToPlace(place) {
    if (gSearchMarker) gSearchMarker.setMap(null);
    if (place.geometry.viewport) {
        gMap.fitBounds(place.geometry.viewport);
    } else {
        gMap.setCenter(place.geometry.location);
        gMap.setZoom(13);
    }
    gSearchMarker = new google.maps.Marker({
        position: place.geometry.location,
        map: gMap,
        title: place.name || place.formatted_address,
        animation: google.maps.Animation.DROP,
        icon: {
            path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
            scale: 6,
            fillColor: '#dc3545',
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 2
        }
    });
}
</script>
@endpush

@push('style')
<style>
/* ── Search box ───────────────────────────── */
.zone-search-wrapper {
    position: relative;
}
.zone-search-inner {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid #d0d5dd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    transition: border-color .2s;
}
.zone-search-inner:focus-within {
    border-color: #0e7cf1;
    box-shadow: 0 0 0 3px rgba(14,124,241,.12);
}
.zone-search-icon {
    padding: 0 10px;
    color: #888;
    font-size: 18px;
    flex-shrink: 0;
}
.zone-search-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 9px 4px;
    font-size: 14px;
    background: transparent;
    color: #333;
}
.zone-search-btn {
    background: #0e7cf1;
    color: #fff;
    border: none;
    padding: 9px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
    flex-shrink: 0;
}
.zone-search-btn:hover { background: #0b68cc; }

/* ── Instructions bar ─────────────────────── */
.zone-instructions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    background: #f8f9fa;
    border: 1px solid #e0e3e8;
    border-radius: 6px;
    padding: 7px 12px;
    font-size: 12px;
    color: #555;
}
.zone-instructions .ms-auto { margin-left: auto; }
.zone-instructions .gap-1 { gap: 4px; }

/* ── Map ──────────────────────────────────── */
.zone-map-wrapper {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #d0d5dd;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
#map {
    width: 100%;
    height: 450px;
}

/* ── Coords badge ─────────────────────────── */
.zone-coords-badge {
    position: absolute;
    bottom: 12px;
    left: 12px;
    background: rgba(14,124,241,.9);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    pointer-events: none;
    backdrop-filter: blur(4px);
}

/* ── Autocomplete dropdown ─────────────────── */
.pac-container {
    border-radius: 0 0 8px 8px;
    border: 1.5px solid #0e7cf1;
    border-top: none;
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
    font-size: 13px;
}
</style>
@endpush
