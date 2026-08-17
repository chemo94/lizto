@if(gs('google_maps_api'))
<script>
(function() {
    var addrInput  = document.getElementById('{{ $addressId ?? 'delivery-addr' }}');
    var latInput   = document.getElementById('{{ $latId ?? 'delivery-lat' }}');
    var lngInput   = document.getElementById('{{ $lngId ?? 'delivery-lng' }}');
    var callbackFn = '{{ $callback ?? 'initGoogleAddress' }}';

    window[callbackFn] = function() {
        if (!addrInput || !window.google || !google.maps || !google.maps.places) return;
        var autocomplete = new google.maps.places.Autocomplete(addrInput, {
            componentRestrictions: { country: '{{ $country ?? 'pe' }}' }
        });
        autocomplete.addListener('place_changed', function() {
            var place = this.getPlace();
            if (place.geometry) {
                if (latInput) latInput.value = place.geometry.location.lat();
                if (lngInput) lngInput.value = place.geometry.location.lng();
            }
        });
    };

    if (!document.querySelector('script[data-google-maps]')) {
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=' + callbackFn;
        script.setAttribute('data-google-maps', '1');
        script.setAttribute('async', '');
        document.head.appendChild(script);
    }
})();
</script>
@endif
