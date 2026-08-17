<?php

namespace App\Http\Controllers\Api\User;

use App\Events\ShoppingItemUpdated;
use App\Events\ShoppingSubstitutionProposed;
use App\Events\ShoppingReceiptUploaded;
use App\Events\FavorStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Favor;
use App\Models\ShoppingBudget;
use App\Models\ShoppingConfirmation;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverShoppingController extends Controller
{
    private function getFavorAndDriver($favorId)
    {
        $driver = auth()->user();
        $favor = Favor::where('id', $favorId)
            ->where('courier_id', $driver->id)
            ->first();

        if (!$favor) {
            return [null, null];
        }
        return [$favor, $driver];
    }

    // ── Store Confirm (courier arrived at store) ──

    public function storeConfirm($favorId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        if (!in_array($favor->shopping_status, ['submitted', 'shopping'])) {
            return apiResponse('invalid_status', 'error', ['Estado invalido para confirmar llegada']);
        }

        $favor->update([
            'shopping_status' => 'shopping',
            'status'          => 'at_pickup',
        ]);

        broadcast(new FavorStatusUpdated($favor, 'at_pickup'))->toOthers();

        return apiResponse('success', 'success', ['Llegada a tienda confirmada']);
    }

    // ── Confirm Item Found ──

    public function confirmItem(Request $request, $favorId, $itemId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->where('status', 'pending')
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado o ya procesado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'actual_price' => 'nullable|numeric|min:0',
            'image'        => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $data = ['status' => 'found', 'store_confirmed_at' => now()];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('shopping/items', 'public');
            $data['image_url'] = Storage::disk('public')->url($path);
        }

        if ($request->actual_price) {
            $data['unit_price'] = $request->actual_price;
            $data['total_price'] = $request->actual_price * $item->quantity;
        }

        $item->update($data);

        broadcast(new ShoppingItemUpdated($favor, $item->fresh(), 'found'))->toOthers();

        return apiResponse('success', 'success', ['Item confirmado', 'item' => $item->fresh()]);
    }

    // ── Mark Item Not Found ──

    public function notFoundItem($favorId, $itemId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->where('status', 'pending')
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado o ya procesado'], 404);
        }

        $item->update([
            'status' => 'not_found',
            'store_confirmed_at' => now(),
        ]);

        broadcast(new ShoppingItemUpdated($favor, $item->fresh(), 'not_found'))->toOthers();

        return apiResponse('success', 'success', [
            'Item marcado como no encontrado',
            'item' => $item->fresh(),
        ]);
    }

    // ── Propose Substitute ──

    public function proposeSubstitute(Request $request, $favorId, $itemId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $item = ShoppingListItem::where('id', $itemId)
            ->where('favor_id', $favor->id)
            ->where('status', 'not_found')
            ->first();

        if (!$item) {
            return apiResponse('not_found', 'error', ['Item no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'substitute_name'  => 'required|string|max:255',
            'substitute_price' => 'required|numeric|min:0',
            'substitute_notes' => 'nullable|string|max:500',
            'image'            => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $data = [
            'substitute_name'  => $request->substitute_name,
            'substitute_price' => $request->substitute_price,
            'substitute_notes' => $request->substitute_notes,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('shopping/substitutes', 'public');
            $data['substitute_image_url'] = Storage::disk('public')->url($path);
        }

        $item->update($data);

        // Update shopping status to awaiting approval
        $favor->update(['shopping_status' => 'awaiting_approval']);

        broadcast(new ShoppingSubstitutionProposed($favor, $item->fresh()))->toOthers();

        return apiResponse('success', 'success', [
            'Sustituto propuesto. Esperando aprobacion del cliente.',
            'item' => $item->fresh(),
        ]);
    }

    // ── Upload Receipt ──

    public function uploadReceipt(Request $request, $favorId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image'         => 'required|file|image|max:5120',
            'actual_total'  => 'required|numeric|min:0',
            'receipt_image' => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        // Save product photo
        $productPath = $request->file('image')->store('shopping/receipts', 'public');
        $productUrl = Storage::disk('public')->url($productPath);

        ShoppingConfirmation::create([
            'favor_id'       => $favor->id,
            'type'           => 'product_photo',
            'image_url'      => $productUrl,
            'created_by_type' => get_class($driver),
            'created_by_id'  => $driver->id,
        ]);

        // Save receipt if provided
        $receiptConfirmation = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('shopping/receipts', 'public');
            $receiptUrl = Storage::disk('public')->url($receiptPath);

            $receiptConfirmation = ShoppingConfirmation::create([
                'favor_id'       => $favor->id,
                'type'           => 'receipt',
                'image_url'      => $receiptUrl,
                'total_amount'   => $request->actual_total,
                'created_by_type' => get_class($driver),
                'created_by_id'  => $driver->id,
            ]);

            $favor->update(['receipt_url' => $receiptUrl]);
        }

        // Update favor
        $favor->update([
            'actual_total'   => $request->actual_total,
            'store_photo_url' => $productUrl,
            'shopping_status' => 'purchased',
        ]);

        // Update budget actual cost
        $budget = $favor->budget;
        if ($budget) {
            $budget->update(['actual_product_cost' => $request->actual_total]);
        }

        broadcast(new ShoppingReceiptUploaded($favor, $receiptConfirmation))->toOthers();

        return apiResponse('success', 'success', [
            'Comprobante subido. Esperando aprobacion del cliente.',
            'actual_total' => $request->actual_total,
        ]);
    }

    // ── Get Shopping Checklist ──

    public function getChecklist($favorId)
    {
        [$favor, $driver] = $this->getFavorAndDriver($favorId);

        if (!$favor) {
            return apiResponse('not_found', 'error', ['Favor no encontrado'], 404);
        }

        $items = $favor->shoppingItems()->orderBy('sort_order')->get();
        $budget = $favor->budget;

        return apiResponse('success', 'success', [
            'favor'             => $favor,
            'items'             => $items,
            'budget'            => $budget,
            'progress'          => $favor->shopping_progress,
            'shopping_status'   => $favor->shopping_status,
            'store_name'        => $favor->store_name,
            'store_address'     => $favor->store_address,
            'delivery_address'  => $favor->delivery_address,
        ]);
    }
}
