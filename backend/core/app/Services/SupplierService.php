<?php

namespace App\Services;

use App\Models\InvSupplier;
use Illuminate\Http\Request;

class SupplierService
{
    private int $sellerId;

    public function __construct(int $sellerId)
    {
        $this->sellerId = $sellerId;
    }

    public function list()
    {
        return InvSupplier::where('seller_id', $this->sellerId)
            ->withCount(['purchases', 'purchaseOrders'])
            ->orderBy('name')
            ->get();
    }

    public function create(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:120',
            'document_type' => 'nullable|string|in:6,1',
            'document_number' => 'nullable|string|max:20',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:120',
            'address'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:120',
        ]);

        return InvSupplier::create([
            'seller_id'        => $this->sellerId,
            'name'             => $request->name,
            'document_type'    => $request->document_type ?? '6',
            'document_number'  => $request->document_number,
            'phone'            => $request->phone,
            'email'            => $request->email,
            'address'          => $request->address,
            'contact_person'   => $request->contact_person,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $supplier = InvSupplier::where('seller_id', $this->sellerId)->findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:120',
            'document_type' => 'nullable|string|in:6,1',
            'document_number' => 'nullable|string|max:20',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:120',
            'address'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:120',
        ]);

        $supplier->update($request->only([
            'name', 'document_type', 'document_number', 'phone', 'email', 'address', 'contact_person',
        ]));

        return $supplier;
    }

    public function delete(int $id)
    {
        $supplier = InvSupplier::where('seller_id', $this->sellerId)->findOrFail($id);

        if ($supplier->purchases()->exists() || $supplier->purchaseOrders()->exists()) {
            return false;
        }

        $supplier->delete();
        return true;
    }
}
