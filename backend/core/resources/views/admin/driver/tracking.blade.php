@extends('admin.layouts.app')
@section('panel')
<p class="lz-subtitle">Conductores y repartidores · Últimas posiciones reportadas por las aplicaciones.</p>
<div class="card lz-fleet">
    <aside class="lz-fleet-list"><h5>Flota <span class="badge badge--primary">{{ $drivers->total() }}</span></h5>
        <form method="GET" class="d-flex gap-2 my-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar conductor..." aria-label="Buscar conductor"><button class="btn btn--primary" aria-label="Buscar"><i class="las la-search"></i></button></form>
        <p class="text-muted small">Selecciona una persona para seguir su ubicación.</p>
        @forelse($drivers as $driver)
        <button type="button" class="lz-driver" data-driver="{{ $driver->id }}" aria-pressed="false"><span class="d-flex justify-content-between align-items-center gap-2"><strong><i class="las la-car me-2"></i>{{ $driver->fullname }}</strong><span class="badge badge--{{ $driver->online_status ? 'success' : 'secondary' }}">{{ $driver->online_status ? 'En línea' : 'Desconectado' }}</span></span><small>{{ ['ride'=>'Taxi','delivery'=>'Delivery','both'=>'Taxi y Delivery'][$driver->service_type] ?? 'Conductor' }}</small><small data-updated>{{ $driver->last_location_fetch_at ? 'Último reporte: '.showDateTime($driver->last_location_fetch_at) : 'Sin fecha de reporte' }}</small></button>
        <div data-driver-detail="{{ $driver->id }}" hidden>
            @if($trip = $assignedRides->get($driver->id))
                <div class="lz-trip"><a href="{{ route('admin.rides.detail',$trip->id) }}">Viaje #{{ $trip->id }}</a><p class="small mt-2">{!! $trip->statusBadge !!}</p><ol class="lz-timeline"><li><small>Origen</small><span>{{ $trip->pickup_location }}</span></li><li><small>Destino</small><span>{{ $trip->destination }}</span></li></ol></div>
            @endif
            @foreach($assignedOrders->get($driver->id, collect()) as $order)
                <div class="lz-trip"><a href="{{ route('admin.delivery.order.detail',$order->id) }}">Pedido {{ $order->order_no }}</a><p class="small text-muted mt-2">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','preparing'=>'En preparación','ready'=>'Listo','on_way'=>'En camino'][$order->status] ?? $order->status }}</p><ol class="lz-timeline"><li><small>Recogida</small><span>{{ $order->store?->name ?? 'Tienda no disponible' }}</span></li><li><small>Entrega</small><span>{{ $order->delivery_address }}</span></li></ol></div>
            @endforeach
            @if(!$assignedRides->has($driver->id) && !$assignedOrders->has($driver->id))<p class="small text-muted">No hay servicios asignados visibles con tus permisos.</p>@endif
            <p class="small text-muted">Servicios consultados al abrir esta página.</p>
        </div>
        <a class="small" href="{{ route('admin.driver.location',$driver->id) }}">Ver detalle de ubicación <i class="las la-arrow-right"></i></a>
        @empty<div class="lz-empty"><i class="las la-map-marker"></i>No se encontraron conductores.</div>@endforelse
        <div class="mt-4">{{ $drivers->links() }}</div>
    </aside>
    <section class="lz-map"><div id="fleet-map" style="height:600px" aria-label="Mapa de últimas ubicaciones"></div><div class="lz-map-message" id="fleet-status" role="status">Cargando posiciones…</div></section>
</div>
@endsection
@push('script')
<script>
(() => {
    const drivers = @json($positions);
    const status = document.querySelector('#fleet-status');
    const markers = new Map();
    let map, selected = null, busy = false;
    const valid = d => d && d.lat !== null && d.lat !== '' && d.lng !== null && d.lng !== '' && Number.isFinite(Number(d.lat)) && Number.isFinite(Number(d.lng)) && Math.abs(Number(d.lat)) <= 90 && Math.abs(Number(d.lng)) <= 180;
    const message = d => `${d.name} · ${d.updated ? 'Último reporte: '+d.updated : 'Sin fecha de reporte'}`;
    const setMarker = d => {
        if (!map || !valid(d)) return;
        const position = {lat:Number(d.lat),lng:Number(d.lng)};
        if(markers.has(d.id)) markers.get(d.id).setPosition(position);
        else {
            const marker = new google.maps.Marker({map,position,title:d.name,icon:{path:google.maps.SymbolPath.CIRCLE,scale:10,fillColor:'#696cff',fillOpacity:1,strokeColor:'#fff',strokeWeight:3}});
            marker.addListener('click',()=>select(d.id)); markers.set(d.id,marker);
        }
    };
    function select(id) {
        selected = drivers.find(d=>d.id===id);
        document.querySelectorAll('[data-driver]').forEach(button=>{
            const active=Number(button.dataset.driver)===id;button.classList.toggle('active',active);button.setAttribute('aria-pressed',String(active));
        });
        document.querySelectorAll('[data-driver-detail]').forEach(detail=>{detail.hidden=Number(detail.dataset.driverDetail)!==id;});
        status.textContent=valid(selected)?message(selected):`${selected.name} · Ubicación no disponible`;
        if(map && valid(selected)){map.panTo({lat:Number(selected.lat),lng:Number(selected.lng)});map.setZoom(15);}
    }
    document.querySelectorAll('[data-driver]').forEach(button=>button.addEventListener('click',()=>select(Number(button.dataset.driver))));
    window.initLiztoFleet = () => {
        const located=drivers.filter(valid);
        if(!located.length){document.querySelector('#fleet-map').style.display='none';status.textContent='No hay ubicaciones reportadas en esta página. Selecciona una persona para consultar su estado.';return;}
        document.querySelector('#fleet-map').style.display='block';
        map=new google.maps.Map(document.querySelector('#fleet-map'),{center:{lat:Number(located[0].lat),lng:Number(located[0].lng)},zoom:13,streetViewControl:false,mapTypeControl:false,styles:[{featureType:'poi',stylers:[{visibility:'off'}]},{featureType:'transit',stylers:[{visibility:'off'}]},{featureType:'landscape',stylers:[{color:'#f5f5f9'}]},{featureType:'water',stylers:[{color:'#d9e1e8'}]},{featureType:'road',elementType:'geometry',stylers:[{color:'#ffffff'}]}]});
        const bounds=new google.maps.LatLngBounds();located.forEach(d=>{setMarker(d);bounds.extend({lat:Number(d.lat),lng:Number(d.lng)});});
        if(located.length>1)map.fitBounds(bounds);status.textContent=`${located.length} ubicaciones reportadas · Selecciona un conductor`;
    };
    async function refresh(){
        if(!selected || document.hidden || busy)return;
        busy=true;
        const driver=selected;
        try {
            const response=await fetch(driver.url,{headers:{Accept:'application/json'},signal:AbortSignal.timeout(8000)});
            if(!response.ok)throw new Error('No disponible');
            const data=await response.json();if(!data.success)throw new Error('No disponible');
            driver.lat=data.latitude;driver.lng=data.longitude;driver.updated=data.last_location_fetch_at;
            if(!map && window.google?.maps && valid(driver))window.initLiztoFleet();
            setMarker(driver);
            const label=document.querySelector(`[data-driver="${driver.id}"] [data-updated]`);
            if(label)label.textContent=driver.updated?'Último reporte: '+driver.updated:'Sin fecha de reporte';
            if(selected===driver)status.textContent=valid(driver)?message(driver):`${driver.name} · Ubicación no disponible`;
        }catch(error){if(selected===driver)status.textContent='No se pudo actualizar la ubicación. Reintentando…';}
        finally {busy=false;}
    }
    const timer=setInterval(refresh,10000);window.addEventListener('pagehide',()=>clearInterval(timer));
    window.gm_authFailure=()=>{status.textContent='El proveedor de mapas no pudo autorizar la conexión. Revisa la configuración de Google Maps.';};
    @if(!gs('google_maps_api'))
    document.querySelector('#fleet-map').style.display='none';status.textContent='Configura Google Maps para visualizar las ubicaciones en el mapa.';
    @endif
})();
</script>
@if(gs('google_maps_api'))<script async src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&callback=initLiztoFleet" onerror="document.querySelector('#fleet-status').textContent='No se pudo cargar el mapa. Comprueba la conexión.'"></script>@endif
@endpush

