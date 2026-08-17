@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-chart-bar"></i></span> Reportes de Costos y Valoración
@endsection

@section('seller-content')
<div class="s-content">

    <!-- RESUMEN -->
    <div class="s-grid-4" style="margin-bottom:20px">
        <div class="s-stat">
            <div class="s-stat-icon green"><i class="las la-wallet"></i></div>
            <div>
                <strong>S/ {{ number_format($valuation, 2) }}</strong>
                <small>Valorización del Inventario</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon blue"><i class="las la-boxes"></i></div>
            <div>
                <strong>{{ $items->count() }}</strong>
                <small>Ítems en Stock</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon amber"><i class="las la-exclamation-triangle"></i></div>
            <div>
                <strong>{{ $items->filter(fn($i) => $i->isLowStock())->count() }}</strong>
                <small>Insumos con Stock Bajo</small>
            </div>
        </div>
        <div class="s-stat" style="cursor:pointer" onclick="exportToCSV()">
            <div class="s-stat-icon purple"><i class="las la-file-excel"></i></div>
            <div>
                <strong>Exportar</strong>
                <small>Descargar Reporte en CSV</small>
            </div>
        </div>
    </div>

    <div class="s-grid-2" style="grid-template-columns: 1.3fr 1fr; gap: 24px; align-items: start;">
        
        <!-- DETALLE DE VALORIZACIÓN -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-calculator"></i> Valorización por Producto/Insumo</h3>
            <div class="s-table-responsive" style="margin-top:15px">
                <table class="s-table" id="valuation-table">
                    <thead>
                        <tr>
                            <th>Insumo / Producto</th>
                            <th style="text-align: right;">Stock Actual</th>
                            <th style="text-align: right;">Costo Prom. (CPP)</th>
                            <th style="text-align: right;">Valor del Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php $valStock = $item->stock * $item->cost; @endphp
                        <tr>
                            <td>
                                <b>{{ $item->name }}</b>
                                <br><small style="color:var(--s-text-3)">{{ ucfirst($item->category ?? 'Sin Categoría') }}</small>
                            </td>
                            <td style="text-align: right; font-weight: 600;">
                                {{ number_format($item->stock, 2) }} <small style="color:var(--s-text-3)">{{ $item->unit }}</small>
                            </td>
                            <td style="text-align: right;">S/ {{ number_format($item->cost, 2) }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--s-accent-dark);">
                                S/ {{ number_format($valStock, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VARIACIÓN DE PRECIOS -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-chart-line"></i> Historial de Variación de Costos</h3>
            <p style="font-size:12px;color:var(--s-text-3);margin:5px 0 15px">
                Costo promedio de compra registrado por mes para cada insumo.
            </p>

            <div class="s-table-responsive">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Insumo</th>
                            <th style="text-align: right;">Costo Prom. Compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compras as $c)
                        <tr>
                            <td><b>{{ $c->month }}</b></td>
                            <td>{{ $c->name }}</td>
                            <td style="text-align: right; font-weight: 600; color:var(--s-accent-dark)">
                                S/ {{ number_format($c->avg_cost, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center;padding:30px">
                                Sin compras registradas para mostrar variaciones.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
function exportToCSV() {
    var csv = [];
    var rows = document.querySelectorAll("#valuation-table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length; j++) {
            // Clean text
            var cleanText = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
            row.push('"' + cleanText + '"');
        }
        
        csv.push(row.join(","));        
    }

    // Download CSV
    var csvFile = new Blob(["\ufeff" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    var downloadLink = document.createElement("a");
    downloadLink.download = "Valorizacion-Inventario-" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
@endsection
