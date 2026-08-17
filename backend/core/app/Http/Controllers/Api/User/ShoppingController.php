<?php

namespace App\Http\Controllers\Api\User;

use App\Events\ShoppingItemUpdated;
use App\Events\ShoppingSubstitutionProposed;
use App\Events\ShoppingReceiptUploaded;
use App\Http\Controllers\Controller;
use App\Models\Favor;
use App\Models\ShoppingBudget;
use App\Models\ShoppingConfirmation;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShoppingController extends Controller
{
    // ── Shopping List Items ──

    public function addItem(Request $request, $favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->where('type', 'buy')
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        if (!in_array($favor->shopping_status, ['preparing', 'submitted'])) {
            return apiResponse('invalid_status', 'error', ['No se pueden agregar items en este estado']);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $maxSort = $favor->shoppingItems()->max('sort_order') ?? 0;

        $item = ShoppingListItem::create([
            'favor_id'    => $favor->id,
            'name'        => $request->name,
            'quantity'    => $request->quantity,
            'unit_price'  => $request->unit_price,
            'total_price' => $request->unit_price ? $request->unit_price * $request->quantity : null,
            'notes'       => $request->notes,
            'sort_order'  => $maxSort + 1,
        ]);

        return apiResponse('success', 'success', ['Item agregado', 'item' => $item]);
    }

    public function updateItem(Request $request, $favorId, $itemId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado'], 404);
        }

        if ($item->status !== 'pending') {
            return apiResponse('invalid_status', 'error', ['No se puede modificar un item ya confirmado']);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|string|max:255',
            'quantity'   => 'sometimes|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $data = $request->only(['name', 'quantity', 'unit_price', 'notes']);
        if (isset($data['unit_price']) && isset($data['quantity'])) {
            $data['total_price'] = $data['unit_price'] * $data['quantity'];
        } elseif (isset($data['unit_price'])) {
            $data['total_price'] = $data['unit_price'] * $item->quantity;
        } elseif (isset($data['quantity'])) {
            $data['total_price'] = $item->unit_price ? $item->unit_price * $data['quantity'] : null;
        }

        $item->update($data);

        return apiResponse('success', 'success', ['Item actualizado', 'item' => $item]);
    }

    public function removeItem($favorId, $itemId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado'], 404);
        }

        if (!in_array($favor->shopping_status, ['preparing'])) {
            return apiResponse('invalid_status', 'error', ['No se pueden eliminar items en este estado']);
        }

        $item->delete();

        return apiResponse('success', 'success', ['Item eliminado']);
    }

    public function uploadItemImage(Request $request, $favorId, $itemId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado'], 404);
        }

        if (!$request->hasFile('image')) {
            return apiResponse('no_image', 'error', ['Imagen requerida']);
        }

        $file = $request->file('image');
        $path = $file->store('shopping/items', 'public');
        $url = Storage::disk('public')->url($path);

        $item->update(['image_url' => $url]);

        return apiResponse('success', 'success', ['Imagen subida', 'image_url' => $url]);
    }

    public function getShoppingList($favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $items = $favor->shoppingItems()->orderBy('sort_order')->get();
        $budget = $favor->budget;

        return apiResponse('success', 'success', [
            'items'         => $items,
            'budget'        => $budget,
            'progress'      => $favor->shopping_progress,
            'shopping_status' => $favor->shopping_status,
            'summary'       => $favor->shopping_summary,
        ]);
    }

    // ── Budget ──

    public function setBudget(Request $request, $favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'max_product_budget' => 'required|numeric|min:0',
            'max_delivery_fee'   => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|in:cash,wallet,mp',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $budget = ShoppingBudget::updateOrCreate(
            ['favor_id' => $favor->id],
            [
                'max_product_budget' => $request->max_product_budget,
                'max_delivery_fee'   => $request->max_delivery_fee,
                'payment_method'     => $request->payment_method ?? 'cash',
            ]
        );

        return apiResponse('success', 'success', ['Presupuesto guardado', 'budget' => $budget]);
    }

    // ── Submit Shopping ──

    public function submitShopping($favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->where('type', 'buy')
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        if ($favor->shopping_status !== 'preparing') {
            return apiResponse('invalid_status', 'error', ['La solicitud ya fue enviada']);
        }

        $itemCount = $favor->shoppingItems()->count();
        if ($itemCount === 0) {
            return apiResponse('empty_list', 'error', ['Agrega al menos un producto']);
        }

        $favor->update([
            'shopping_status' => 'submitted',
            'status'          => 'searching_courier',
        ]);

        // Broadcast to nearby couriers
        broadcast(new \App\Events\NewJobAvailable($favor, 'favor'))->toOthers();

        // Send push to all couriers
        \App\Services\FcmService::sendToAllCouriers(
            'Nuevo favor de compra',
            $favor->description,
            ['favor_id' => $favor->id, 'order_no' => $favor->order_no, 'type' => 'new_favor']
        );

        return apiResponse('success', 'success', ['Solicitud enviada a repartidores']);
    }

    // ── Substitution Approval ──

    public function approveSubstitution(Request $request, $favorId, $itemId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->where('status', 'not_found')
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado o no requiere aprobacion'], 404);
        }

        $validator = Validator::make($request->all(), [
            'approved'           => 'required|boolean',
            'replacement_item'   => 'nullable|string|max:255',
            'replacement_price'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $item->update([
            'customer_approved'   => $request->approved,
            'customer_approved_at' => now(),
            'status'              => $request->approved ? 'substituted' : 'cancelled',
            'substitute_name'     => $request->approved ? ($request->replacement_item ?? $item->substitute_name) : null,
            'substitute_price'    => $request->approved ? ($request->replacement_price ?? $item->substitute_price) : null,
        ]);

        // Check if all substitutions are resolved
        $pendingApprovals = $favor->shoppingItems()
            ->where('status', 'not_found')
            ->whereNotNull('substitute_name')
            ->whereNull('customer_approved_at')
            ->count();

        if ($pendingApprovals === 0 && $favor->shopping_status === 'awaiting_approval') {
            $favor->update(['shopping_status' => 'purchasing']);
        }

        return apiResponse('success', 'success', [
            $request->approved ? 'Sustituto aprobado' : 'Sustituto rechazado',
            'item' => $item->fresh(),
        ]);
    }

    // ── Confirm Purchase (after receipt) ──

    public function confirmPurchase(Request $request, $favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        if ($favor->shopping_status !== 'purchased') {
            return apiResponse('invalid_status', 'error', ['Esperando que el repartidor suba el comprobante']);
        }

        $validator = Validator::make($request->all(), [
            'approved'         => 'required|boolean',
            'payment_method'   => 'nullable|in:cash,wallet,mp',
            'extra_amount'     => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        if (!$request->approved) {
            $favor->update([
                'shopping_status' => 'cancelled',
                'status'          => 'cancelled',
                'cancelled_at'    => now(),
                'cancelled_by'    => 'user',
                'cancel_reason'   => 'Compra no aprobada por el cliente',
            ]);
            return apiResponse('success', 'success', ['Compra cancelada']);
        }

        // Update budget with actual cost
        $budget = $favor->budget;
        if ($budget) {
            $paymentMethod = $request->payment_method ?? $budget->payment_method;
            $budget->update([
                'actual_product_cost' => $favor->actual_total,
                'payment_method'      => $paymentMethod,
                'payment_status'      => $paymentMethod === 'cash' ? 'collecting' : 'prepaid',
            ]);
        }

        // Update total
        $productCost = $favor->actual_total ?? 0;
        $deliveryFee = $budget?->actual_delivery_fee ?? $budget?->max_delivery_fee ?? $favor->delivery_fee;
        $extraAmount = $request->extra_amount ?? 0;
        $newTotal = $productCost + $deliveryFee + $extraAmount;

        $favor->update([
            'shopping_status' => 'delivering',
            'status'          => 'on_way_to_delivery',
            'total'           => $newTotal,
        ]);

        return apiResponse('success', 'success', [
            'Compra aprobada. El repartidor esta en camino.',
            'total' => $newTotal,
        ]);
    }

    // ── Shopping Status ──

    public function getShoppingStatus($favorId)
    {
        $favor = Favor::where('id', $favorId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        return apiResponse('success', 'success', [
            'shopping_status'   => $favor->shopping_status,
            'progress'          => $favor->shopping_progress,
            'needs_approval'    => $favor->needs_substitution_approval,
            'summary'           => $favor->shopping_summary,
            'items'             => $favor->shoppingItems()->orderBy('sort_order')->get(),
            'budget'            => $favor->budget,
            'actual_total'      => $favor->actual_total,
            'receipt_url'       => $favor->receipt_url,
            'store_photo_url'   => $favor->store_photo_url,
        ]);
    }
}
