<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguimiento de envío {{ $favor->order_no }}</title>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif
        }

        .wrap {
            max-width: 960px;
            margin: auto;
            padding: 18px
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px #0f172a14;
            overflow: hidden
        }

        .head {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center
        }

        .head h1 {
            font-size: 18px;
            margin: 0
        }

        .status {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 99px;
            background: #dbeafe;
            color: #1d4ed8
        }

        #map {
            height: min(68vh, 600px);
            min-height: 380px
        }

        .info {
            padding: 14px 20px;
            display: grid;
            gap: 8px;
            font-size: 13px
        }

        .point {
            display: flex;
            gap: 8px;
            align-items: flex-start
        }

        .dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            margin-top: 3px;
            flex: none
        }

        .muted {
            color: #64748b
        }

        .courier-info {
            padding: 12px 20px;
            border-top: 1px solid #e2e8f0;
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 13px
        }

        .courier-info.active {
            display: flex
        }

        .courier-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1d4ed8;
            font-size: 15px;
            flex: none
        }

        .courier-detail {
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .courier-name {
            font-weight: 700;
            color: #0f172a
        }

        .courier-phone {
            color: #1d4ed8;
            text-decoration: none;
            font-size: 12px
        }

        .courier-route-marker {
            width: 52px;
            height: 52px;
            transform-origin: 50% 50%;
            transition: transform .22s linear
        }

        .courier-route-marker img {
            display: block;
            width: 52px;
            height: 52px;
            object-fit: contain;
            filter: drop-shadow(0 3px 4px rgba(15, 23, 42, .35))
        }

        @media(max-width:600px) {
            .wrap {
                padding: 0
            }

            .card {
                border-radius: 0
            }

            .head {
                align-items: flex-start;
                flex-direction: column
            }

            #map {
                min-height: 420px
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <div>
                    <h1>Seguimiento en tiempo real</h1>
                    <div class="muted">Envío #{{ $favor->order_no }}</div>
                </div><span id="status" class="status">Actualizando…</span>
            </div>
            @if($favor->pickup_lat && $favor->pickup_lng && $favor->delivery_lat && $favor->delivery_lng && gs('google_maps_api'))
            <div id="map"></div>
            @else
            <div style="padding:50px;text-align:center" class="muted">Este envío no tiene coordenadas disponibles para mostrar el mapa.</div>
            @endif
            <div class="info">
                <div class="point"><span class="dot" style="background:#16a34a"></span>
                    <div><b>Recojo</b>
                        <div class="muted">{{ $favor->pickup_address }}</div>
                    </div>
                </div>
                <div class="point"><span class="dot" style="background:#ef4444"></span>
                    <div><b>Entrega</b>
                        <div class="muted">{{ $favor->delivery_address }}</div>
                    </div>
                </div>
                <div id="leg" class="muted">Esperando ubicación del repartidor…</div>
            </div>
            <div id="courier-box" class="courier-info">
                <div id="courier-avatar" class="courier-avatar">R</div>
                <div class="courier-detail">
                    <span id="courier-name" class="courier-name">Repartidor</span>
                    <a id="courier-phone" class="courier-phone" href="#">—</a>
                </div>
            </div>
        </div>
    </div>
    @if($favor->pickup_lat && $favor->pickup_lng && $favor->delivery_lat && $favor->delivery_lng && gs('google_maps_api'))
    <script>
        const endpoint = @json(route('tracking.favor.data', $token));
        const labels = {
            accepted: 'Repartidor asignado',
            on_way_to_pickup: 'En camino al recojo',
            at_pickup: 'En el punto de recojo',
            on_way_to_delivery: 'En camino a la entrega',
            delivered: 'Entrega completada',
            cancelled: 'Envío cancelado',
            searching_courier: 'Buscando repartidor'
        };
        let map, courierMarker, directions, renderer, lastRouteKey = '', pickup, delivery;
        let courierPosition = null, courierHeading = 0, courierAnimationFrame = null, routePoints = [], CourierOverlay = null;

        function initMap() {
            pickup = {
                    lat: {{(float) $favor->pickup_lat}},
                    lng: {{(float) $favor->pickup_lng}}
                };
            delivery = {
                    lat: {{(float) $favor->delivery_lat}},
                    lng: {{(float) $favor->delivery_lng}}
                };
            map = new google.maps.Map(document.getElementById('map'), {
                center: pickup,
                zoom: 14,
                mapTypeControl: false,
                streetViewControl: false
            });
            directions = new google.maps.DirectionsService();
            defineCourierOverlay();
            renderer = new google.maps.DirectionsRenderer({
                map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#2563eb',
                    strokeWeight: 5,
                    strokeOpacity: .85
                }
            });
            marker(pickup, 'R', '#16a34a', 'Recojo');
            marker(delivery, 'E', '#ef4444', 'Entrega');
            const b = new google.maps.LatLngBounds();
            b.extend(pickup);
            b.extend(delivery);
            map.fitBounds(b);
            refresh();
            setInterval(refresh, 4000)
        }

        function marker(pos, label, color, title) {
            return new google.maps.Marker({
                position: pos,
                map,
                title,
                label: {
                    text: label,
                    color: '#fff',
                    fontWeight: 'bold'
                },
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: color,
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2
                }
            })
        }

        function defineCourierOverlay() {
            if (CourierOverlay) return;
            CourierOverlay = function(position, heading) {
                this.position = position;
                this.heading = heading || 0;
                this.element = null;
            };
            CourierOverlay.prototype = new google.maps.OverlayView();
            CourierOverlay.prototype.onAdd = function() {
                this.element = document.createElement('div');
                this.element.title = 'Repartidor';
                this.element.style.cssText = 'position:absolute;left:0;top:0;width:52px;height:52px;will-change:transform;pointer-events:none;z-index:5;';
                this.element.innerHTML = '<div class="courier-route-marker"><img src="{{ asset('assets/images/delivery_man_marker.png') }}" alt="Repartidor"></div>';
                this.getPanes().overlayMouseTarget.appendChild(this.element);
                this.updateVisual();
            };
            CourierOverlay.prototype.draw = function() {
                if (!this.element || !this.position) return;
                const point = this.getProjection().fromLatLngToDivPixel(new google.maps.LatLng(this.position.lat, this.position.lng));
                if (point) {
                    // Translate the overlay itself on every animation frame.
                    // This is GPU-composited and avoids Google Maps reusing a stale left/top value.
                    this.element.style.transform = 'translate3d(' + (point.x - 26) + 'px,' + (point.y - 26) + 'px,0)';
                }
            };
            CourierOverlay.prototype.onRemove = function() {
                if (this.element) this.element.remove();
                this.element = null;
            };
            CourierOverlay.prototype.setPosition = function(position) {
                this.position = position;
                this.draw();
            };
            CourierOverlay.prototype.setHeading = function(heading) {
                this.heading = heading;
                this.updateVisual();
            };
            CourierOverlay.prototype.updateVisual = function() {
                if (!this.element) return;
                const icon = this.element.querySelector('.courier-route-marker');
                if (icon) icon.style.transform = 'rotate(' + this.heading + 'deg)';
            };
        }

        function setCourierPosition(target) {
            if (!courierMarker) {
                courierPosition = target;
                courierHeading = routeHeadingAt(target) || 0;
                courierMarker = new CourierOverlay(target, courierHeading);
                courierMarker.setMap(map);
                return;
            }

            if (courierAnimationFrame) cancelAnimationFrame(courierAnimationFrame);
            const from = { ...courierPosition };
            const initialHeading = courierHeading;
            const targetHeading = routeHeadingAt(target) || bearingBetween(from, target) || courierHeading;
            const movementPath = routeAnimationPath(from, target);
            const pathLengths = movementPath.slice(1).map((point, index) => mapDistance(movementPath[index], point));
            const totalLength = pathLengths.reduce((total, length) => total + length, 0);
            let startedAt = null;
            const duration = 3600;

            const animate = timestamp => {
                if (!startedAt) startedAt = timestamp;
                const progress = Math.min((timestamp - startedAt) / duration, 1);
                const position = pointOnPath(movementPath, pathLengths, totalLength, progress);
                const heading = routeHeadingAt(position) || interpolateHeading(initialHeading, targetHeading, progress);
                courierPosition = position;
                courierHeading = heading;
                courierMarker.setPosition(position);
                courierMarker.setHeading(heading);
                if (progress < 1) courierAnimationFrame = requestAnimationFrame(animate);
                else courierAnimationFrame = null;
            };
            courierAnimationFrame = requestAnimationFrame(animate);
        }

        function routeHeadingAt(position) {
            if (routePoints.length < 2) return null;
            const nearest = nearestRouteIndex(position);
            const next = routePoints[Math.min(nearest + 1, routePoints.length - 1)];
            const previous = routePoints[Math.max(nearest - 1, 0)];
            return bearingBetween(routePoints[nearest], next) || bearingBetween(previous, routePoints[nearest]);
        }

        function nearestRouteIndex(position) {
            let nearest = 0, nearestDistance = Infinity;
            routePoints.forEach((point, index) => {
                const distance = mapDistance(point, position);
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearest = index;
                }
            });
            return nearest;
        }

        function routeAnimationPath(from, target) {
            if (routePoints.length < 2) return [from, target];
            const startIndex = nearestRouteIndex(from);
            const targetIndex = nearestRouteIndex(target);
            if (targetIndex <= startIndex) return [from, target];
            return [from, ...routePoints.slice(startIndex + 1, targetIndex + 1), target];
        }

        function mapDistance(from, to) {
            const latDelta = from.lat - to.lat;
            const lngDelta = (from.lng - to.lng) * Math.cos(((from.lat + to.lat) / 2) * Math.PI / 180);
            return Math.sqrt(latDelta * latDelta + lngDelta * lngDelta);
        }

        function pointOnPath(path, lengths, totalLength, progress) {
            if (!totalLength) return path[path.length - 1];
            let remaining = totalLength * progress;
            for (let index = 0; index < lengths.length; index++) {
                if (remaining <= lengths[index]) {
                    const ratio = lengths[index] ? remaining / lengths[index] : 1;
                    const from = path[index], to = path[index + 1];
                    return { lat: from.lat + (to.lat - from.lat) * ratio, lng: from.lng + (to.lng - from.lng) * ratio };
                }
                remaining -= lengths[index];
            }
            return path[path.length - 1];
        }

        function bearingBetween(from, to) {
            if (!from || !to || (from.lat === to.lat && from.lng === to.lng)) return null;
            const lat1 = from.lat * Math.PI / 180, lat2 = to.lat * Math.PI / 180;
            const lngDelta = (to.lng - from.lng) * Math.PI / 180;
            const y = Math.sin(lngDelta) * Math.cos(lat2);
            const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(lngDelta);
            return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
        }

        function interpolateHeading(from, to, progress) {
            const difference = ((to - from + 540) % 360) - 180;
            return (from + difference * progress + 360) % 360;
        }

        function applyDirections(result) {
            routePoints = (result.routes[0].overview_path || []).map(point => ({ lat: point.lat(), lng: point.lng() }));
            renderer.setDirections(result);
            if (courierMarker && courierPosition) {
                courierHeading = routeHeadingAt(courierPosition) || courierHeading;
                courierMarker.setHeading(courierHeading);
            }
        }

        function refresh() {
            fetch(endpoint, {
                cache: 'no-store'
            }).then(r => r.json()).then(d => {
                document.getElementById('status').textContent = labels[d.status] || d.status;

                const courierBox = document.getElementById('courier-box');
                const isDelivered = d.status === 'delivered';

                if (d.courier && d.courier.name) {
                    const initials = d.courier.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
                    document.getElementById('courier-avatar').textContent = initials;
                    document.getElementById('courier-name').textContent = d.courier.name;
                    if (d.courier.phone) {
                        document.getElementById('courier-phone').textContent = d.courier.phone;
                        document.getElementById('courier-phone').href = 'tel:' + d.courier.phone;
                    }
                    courierBox.classList.add('active');
                }

                if (isDelivered) {
                    document.getElementById('leg').textContent = 'Envío entregado correctamente.';
                    if (courierMarker) {
                        courierMarker.setMap(null);
                        courierMarker = null;
                    }
                    directions.route({
                        origin: pickup,
                        destination: delivery,
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (result, status) => {
                        if (status === 'OK') applyDirections(result);
                    });
                    return;
                }

                const dest = d.phase === 'delivery' ? d.delivery : d.pickup;
                document.getElementById('leg').textContent = d.phase === 'delivery' ? 'Tramo activo: repartidor → punto de entrega.' : 'Tramo activo: repartidor → punto de recojo.';
                if (!d.courier || !d.courier.latitude || !d.courier.longitude) return;
                const pos = {
                    lat: +d.courier.latitude,
                    lng: +d.courier.longitude
                };
                setCourierPosition(pos);
                const key = [pos.lat.toFixed(4), pos.lng.toFixed(4), dest.lat, dest.lng].join(':');
                if (key !== lastRouteKey) {
                    lastRouteKey = key;
                    directions.route({
                        origin: pos,
                        destination: {
                            lat: +dest.lat,
                            lng: +dest.lng
                        },
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (result, status) => {
                        if (status === 'OK') applyDirections(result)
                    })
                }
            }).catch(() => {
                document.getElementById('leg').textContent = 'No se pudo actualizar la ubicación. Reintentando…'
            })
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&callback=initMap" async defer></script>
    @endif
</body>

</html>
