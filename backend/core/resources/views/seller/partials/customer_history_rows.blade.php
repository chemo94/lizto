@forelse($customers as $c)
<tr class="customer-data-row">
    <td><div style="display:flex;align-items:center;gap:10px"><div style="width:36px;height:36px;border-radius:50%;background:var(--s-accent-light);display:grid;place-items:center;font-size:14px;font-weight:800;color:var(--s-accent-dark);flex-shrink:0;text-transform:uppercase">{{ substr($c->customer_name ?: 'A', 0, 1) }}</div><div><b style="color:var(--s-text)">{{ $c->customer_name ?: 'Anónimo' }}</b>@if($c->total_orders >= 5)<span class="s-badge s-badge-amber" style="font-size:9px;margin-left:6px">⭐ Frecuente</span>@endif</div></div></td>
    <td style="color:var(--s-text-2);font-size:12px;white-space:nowrap">{{ $c->customer_doc ?: '—' }}</td>
    <td style="color:var(--s-text-3);font-size:13px">{{ $c->customer_phone ?: '—' }}</td>
    <td style="color:var(--s-text-3);font-size:11px;min-width:180px;max-width:260px">{{ $c->customer_address ?: '—' }}</td>
    <td><span class="s-badge {{ $c->total_orders >= 5 ? 's-badge-amber' : ($c->total_orders >= 2 ? 's-badge-blue' : 's-badge-gray') }}">{{ $c->total_orders }} pedidos</span></td>
    <td><b style="color:var(--s-accent-dark)">S/ {{ number_format($c->total_spent, 2) }}</b></td>
    <td style="color:var(--s-text-2)">S/ {{ $c->total_orders > 0 ? number_format($c->total_spent / $c->total_orders, 2) : '0.00' }}</td>
    <td style="font-size:12px;color:var(--s-text-3);white-space:nowrap">{{ $c->last_order ? \Carbon\Carbon::parse($c->last_order)->format('d/m/Y H:i') : '—' }}</td>
    <td><button type="button" class="customer-edit-btn" onclick="openCustomerEdit({{ $loop->index }})" title="Editar cliente"><i class="las la-pen"></i></button></td>
</tr>
@empty
<tr><td colspan="9"><div class="s-empty"><i class="las la-user-friends"></i><p>No hay clientes para mostrar</p></div></td></tr>
@endforelse
