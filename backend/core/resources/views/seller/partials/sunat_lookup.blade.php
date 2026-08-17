<div class="sunat-lookup" data-prefix="{{ $prefix ?? 'sunat' }}">
    <div style="display:flex;gap:6px;margin-bottom:6px">
        <select class="s-input" id="{{ $prefix ?? 'sunat' }}-tpdoc" @if(!empty($tpdocName)) name="{{ $tpdocName }}" @endif style="width:70px;padding:4px 6px;height:34px;font-size:11px;background:#fff">
            <option value="1">DNI</option>
            <option value="6" {{ ($defaultType ?? '6') === '6' ? 'selected' : '' }}>RUC</option>
        </select>
        <input class="s-input" id="{{ $prefix ?? 'sunat' }}-numdoc" placeholder="Nº {{ ($defaultType ?? '6') === '6' ? 'RUC' : 'Documento' }}..." value="{{ $defaultValue ?? '' }}" @if(!empty($numdocName)) name="{{ $numdocName }}" @endif style="padding:4px 10px;height:34px;font-size:11px;flex:1;background:#fff">
        <button type="button" class="s-btn s-btn-primary s-btn-xs" style="height:34px;width:34px;justify-content:center;padding:0;border-radius:8px" onclick="sunatLookup('{{ $prefix ?? 'sunat' }}')">
            <i class="las la-search" style="font-size:14px"></i>
        </button>
    </div>
    <div id="{{ $prefix ?? 'sunat' }}-result" style="font-size:11px;font-weight:700;margin-bottom:6px;min-height:14px"></div>
</div>

<script>
if (!window._sunatLookupLoaded) {
    window._sunatLookupLoaded = true;
    window._sunatTargets = {};
}

window._sunatTargets['{{ $prefix ?? 'sunat' }}'] = {
    name: '{{ $nameTarget ?? '' }}',
    trade: '{{ $tradeTarget ?? '' }}',
    address: '{{ $addressTarget ?? '' }}',
    phone: '{{ $phoneTarget ?? '' }}',
};

function sunatLookup(prefix) {
    var num = document.getElementById(prefix + '-numdoc').value.trim(),
        tp  = document.getElementById(prefix + '-tpdoc').value;
    if (!num) { alert('Ingresa un número de documento'); return; }
    if ((tp === '6' && num.length < 11) || (tp === '1' && num.length < 8)) {
        alert('Número de documento inválido'); return;
    }
    var r = document.getElementById(prefix + '-result');
    r.innerHTML = '<span style="color:var(--s-accent)"><i class="las la-spinner la-spin"></i> Consultando SUNAT...</span>';
    fetch('/seller/sunat-lookup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({numdoc: num, tpdoc: tp})
    })
    .then(function(x) { return x.json(); })
    .then(function(d) {
        var targets = window._sunatTargets[prefix] || {};
        if (d.status && d.nombre) {
            if (targets.name) {
                var el = document.getElementById(targets.name);
                if (el) el.value = d.nombre;
            }
            if (targets.trade) {
                var el = document.getElementById(targets.trade);
                if (el && d.nombreComercial) el.value = d.nombreComercial;
            }
            if (targets.address) {
                var el = document.getElementById(targets.address);
                if (el && d.direccion) el.value = d.direccion;
            }
            if (targets.phone) {
                var el = document.getElementById(targets.phone);
                if (el) el.focus();
            }
            r.innerHTML = '<span style="color:var(--s-success)">\u2713 ' + d.nombre + '</span>';
        } else {
            r.innerHTML = '<span style="color:var(--s-danger)">' + (d.result || 'No encontrado') + '</span>';
        }
    })
    .catch(function() {
        r.innerHTML = '<span style="color:var(--s-danger)">Error en la búsqueda</span>';
    });
}
</script>
