<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Favor;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KitchenController extends Controller
{
    /**
     * Render the kitchen display page (no layout / fullscreen).
     */
    public function index()
    {
        $pusherKey     = config('broadcasting.connections.pusher.key');
        $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

        return view('admin.kitchen.index', compact('pusherKey', 'pusherCluster'));
    }

    /**
     * JSON endpoint for polling + initial load.
     * Returns active orders unified from 3 sources.
     */
    public function orders(Request $request)
    {
        $since = $request->query('since'); // ISO timestamp for delta queries

        $items = collect();

        // ── 1. DeliveryOrders (pedidos de tiendas desde app/web) ──
        $deliveryQuery = DeliveryOrder::with(['store', 'user', 'items'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderByDesc('created_at');

        if ($since) {
            $deliveryQuery->where('created_at', '>', Carbon::parse($since));
        }

        $deliveryQuery->get()->each(function ($order) use ($items) {
            $items->push($this->formatDeliveryOrder($order));
        });

        // ── 2. Favors (delivery / encargos) ──
        $favorQuery = Favor::with(['user', 'courier', 'seller'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderByDesc('created_at');

        if ($since) {
            $favorQuery->where('created_at', '>', Carbon::parse($since));
        }

        $favorQuery->get()->each(function ($favor) use ($items) {
            $items->push($this->formatFavor($favor));
        });

        // ── 3. PosOrders — solo los de delivery ──
        $posQuery = PosOrder::with(['store', 'user', 'items'])
            ->whereNotNull('delivery_address')
            ->whereNotIn('status', ['paid', 'cancelled', 'refunded'])
            ->orderByDesc('created_at');

        if ($since) {
            $posQuery->where('created_at', '>', Carbon::parse($since));
        }

        $posQuery->get()->each(function ($order) use ($items) {
            $items->push($this->formatPosOrder($order));
        });

        // Sort all by created_at DESC (most recent first)
        $sorted = $items->sortByDesc('created_at_raw')->values();

        return response()->json([
            'status' => 'success',
            'count'  => $sorted->count(),
            'orders' => $sorted,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // ── Private formatters ──

    private function formatDeliveryOrder($order): array
    {
        $minutes = $order->created_at ? now()->diffInMinutes($order->created_at) : 0;

        return [
            'uid'            => 'do_' . $order->id,
            'type'           => 'delivery_order',
            'type_label'     => 'Pedido Tienda',
            'type_icon'      => 'la-store',
            'type_color'     => '#6366f1',
            'order_no'       => $order->order_no ?? '#' . $order->id,
            'title'          => $order->store->name ?? 'Tienda',
            'subtitle'       => $order->user->fullname ?? 'Cliente',
            'address'        => $order->delivery_address ?? '—',
            'total'          => number_format($order->total ?? 0, 2),
            'currency'       => 'S/',
            'items_count'    => $order->items->count(),
            'items_summary'  => $order->items->take(3)->map(fn($i) => $i->product_name ?? $i->name ?? 'Ítem')->implode(', '),
            'status'         => $order->status,
            'status_label'   => $this->deliveryOrderStatus($order->status),
            'minutes_waiting'=> (int) $minutes,
            'priority'       => $this->calcPriority($minutes, null),
            'created_at_raw' => $order->created_at?->timestamp ?? 0,
            'created_at'     => $order->created_at?->format('d/m H:i') ?? '—',
            'detail_url'     => route('admin.delivery.order.detail', $order->id),
        ];
    }

    private function formatFavor($favor): array
    {
        $minutes = $favor->created_at ? now()->diffInMinutes($favor->created_at) : 0;
        $priority = $favor->priority_level ?? null;

        return [
            'uid'            => 'fv_' . $favor->id,
            'type'           => 'favor',
            'type_label'     => $favor->type === 'buy' ? 'Encargo Shopping' : 'Favor Delivery',
            'type_icon'      => $favor->type === 'buy' ? 'la-shopping-bag' : 'la-motorcycle',
            'type_color'     => $favor->type === 'buy' ? '#f59e0b' : '#10b981',
            'order_no'       => $favor->order_no ?? '#' . $favor->id,
            'title'          => $favor->pickup_address ?? 'Sin dirección',
            'subtitle'       => $favor->user->fullname ?? 'Cliente',
            'address'        => $favor->delivery_address ?? '—',
            'total'          => number_format($favor->total ?? 0, 2),
            'currency'       => 'S/',
            'items_count'    => $favor->is_express ? 1 : ($favor->shoppingItems?->count() ?? 1),
            'items_summary'  => $favor->description ?? '—',
            'status'         => $favor->status,
            'status_label'   => $this->favorStatus($favor->status),
            'minutes_waiting'=> (int) $minutes,
            'priority'       => $this->calcPriority($minutes, $priority),
            'is_express'     => $favor->is_express ?? false,
            'created_at_raw' => $favor->created_at?->timestamp ?? 0,
            'created_at'     => $favor->created_at?->format('d/m H:i') ?? '—',
            'detail_url'     => route('admin.delivery.favor.detail', $favor->id),
        ];
    }

    private function formatPosOrder($order): array
    {
        $minutes = $order->created_at ? now()->diffInMinutes($order->created_at) : 0;

        return [
            'uid'            => 'pos_' . $order->id,
            'type'           => 'pos_order',
            'type_label'     => 'Pedido POS Delivery',
            'type_icon'      => 'la-cash-register',
            'type_color'     => '#ec4899',
            'order_no'       => $order->order_no ?? '#' . $order->id,
            'title'          => $order->store->name ?? 'POS',
            'subtitle'       => $order->user->fullname ?? ($order->customer_name ?? 'Cliente'),
            'address'        => $order->delivery_address ?? '—',
            'total'          => number_format($order->total ?? 0, 2),
            'currency'       => 'S/',
            'items_count'    => $order->items->count(),
            'items_summary'  => $order->items->take(3)->map(fn($i) => $i->product_name ?? $i->name ?? 'Ítem')->implode(', '),
            'status'         => $order->status,
            'status_label'   => $this->posOrderStatus($order->status),
            'minutes_waiting'=> (int) $minutes,
            'priority'       => $this->calcPriority($minutes, null),
            'created_at_raw' => $order->created_at?->timestamp ?? 0,
            'created_at'     => $order->created_at?->format('d/m H:i') ?? '—',
            'detail_url'     => null,
        ];
    }

    private function calcPriority(int $minutes, ?int $priorityLevel): string
    {
        if ($priorityLevel >= 3 || $minutes >= 15) return 'urgent';
        if ($minutes >= 5) return 'normal';
        return 'new';
    }

    private function deliveryOrderStatus(string $status): string
    {
        return match ($status) {
            'pending'   => 'Pendiente',
            'confirmed' => 'Confirmado',
            'preparing' => 'Preparando',
            'ready'     => 'Listo',
            default     => ucfirst($status),
        };
    }

    private function favorStatus(string $status): string
    {
        return match ($status) {
            'pending'             => 'Esperando repartidor',
            'accepted'            => 'Aceptado',
            'on_way_to_pickup'    => 'En camino a recogida',
            'at_pickup'           => 'En punto de recogida',
            'on_way_to_delivery'  => 'En camino al destino',
            default               => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function posOrderStatus(string $status): string
    {
        return match ($status) {
            'pending'    => 'Pendiente',
            'processing' => 'En proceso',
            default      => ucfirst($status),
        };
    }
}
