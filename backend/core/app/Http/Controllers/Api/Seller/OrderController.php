<?php

namespace App\Http\Controllers\Api\Seller;

use App\Events\DeliveryOrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DeviceToken;
use App\Models\Product;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function orders(Request $request)
    {
        $seller = auth()->user();

        $status = $request->status;
        $query  = DeliveryOrder::bySeller($seller->id)->with('items', 'user');

        if ($status && in_array($status, ['pending', 'confirmed', 'preparing', 'ready', 'on_way', 'delivered', 'cancelled'])) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);

        return apiResponse('seller_orders', 'success', ['Pedidos de tu tienda'], [
            'orders'             => $orders,
            'product_image_path' => getFilePath('product'),
            'store_image_path'   => getFilePath('store'),
            'user_image_path'    => getFilePath('user'),
        ]);
    }

    public function detail($id)
    {
        $seller = auth()->user();
        $order  = DeliveryOrder::bySeller($seller->id)
            ->with('items', 'user', 'store')
            ->findOrFail($id);

        return apiResponse('seller_order_detail', 'success', ['Detalle del pedido'], [
            'order'              => $order,
            'product_image_path' => getFilePath('product'),
            'store_image_path'   => getFilePath('store'),
            'user_image_path'    => getFilePath('user'),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $seller = auth()->user();
        $order  = DeliveryOrder::bySeller($seller->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:confirmed,preparing,ready,cancelled',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $newStatus = $request->status;

        $allowedTransitions = [
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$order->status]) || !in_array($newStatus, $allowedTransitions[$order->status])) {
            return apiResponse('invalid_transition', 'error', ['No puedes cambiar a este estado desde ' . $order->status]);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'cancelled') {
            $updateData['cancelled_at']  = now();
            $updateData['cancel_reason'] = $request->reason ?? 'Cancelado por la tienda';
        }

        $order->update($updateData);
        $order->load('items', 'store', 'user');

        event(new DeliveryOrderStatusUpdated($order));

        // Notificar repartidores cuando la tienda CONFIRMA o tiene LISTO el pedido
        if (in_array($newStatus, ['confirmed', 'ready'])) {
            broadcast(new \App\Events\NewJobAvailable($order, 'Nuevo pedido para reparto en ' . ($order->store->name ?? 'tienda')))->toOthers();
            $this->sendFcmToNearbyCouriers($order);
        }

        // Notificar al usuario en cada cambio de estado
        if ($order->user) {
            $statusLabels = [
                'confirmed' => ['Pedido confirmado', 'Tu pedido #' . $order->order_no . ' ha sido confirmado por la tienda'],
                'preparing' => ['Preparando tu pedido', 'La tienda está preparando tu pedido #' . $order->order_no],
                'ready'     => ['Pedido listo', 'Tu pedido #' . $order->order_no . ' está listo para ser entregado'],
                'cancelled' => ['Pedido cancelado', 'Tu pedido #' . $order->order_no . ' ha sido cancelado por la tienda'],
            ];
            if (isset($statusLabels[$newStatus])) {
                FcmService::sendToUser($order->user, $statusLabels[$newStatus][0], $statusLabels[$newStatus][1], [
                    'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'order_status', 'status' => $newStatus,
                    'store_name' => $order->store->name ?? '',
                ]);
            }
        }

        return apiResponse('status_updated', 'success', ['Estado actualizado a ' . $newStatus], [
            'order' => $order,
        ]);
    }

    private function sendFcmToNearbyCouriers($order)
    {
        FcmService::sendToAllCouriers(
            'Nuevo pedido disponible',
            'Hay un pedido listo para reparto en ' . ($order->store->name ?? 'tu zona'),
            ['job_id' => (string) $order->id, 'order_no' => $order->order_no, 'job_type' => 'delivery']
        );
    }
}
