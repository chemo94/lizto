<?php

namespace App\Services;

use App\Models\InvKardex;

class KardexService
{
    public static function create(
        int $sellerId,
        int $itemId,
        string $type,
        float $quantity,
        float $unitCost,
        float $balanceStock,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $warehouseId = null
    ): InvKardex {
        return InvKardex::create([
            'seller_id'      => $sellerId,
            'item_id'        => $itemId,
            'warehouse_id'   => $warehouseId,
            'type'           => $type,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'quantity'       => $type === 'salida' ? -abs($quantity) : abs($quantity),
            'unit_cost'      => $unitCost,
            'total_cost'     => abs($quantity) * $unitCost,
            'balance_stock'  => $balanceStock,
            'description'    => $description,
        ]);
    }

    public static function entry(
        int $sellerId,
        int $itemId,
        float $quantity,
        float $unitCost,
        float $balanceStock,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $warehouseId = null
    ): InvKardex {
        return self::create($sellerId, $itemId, 'entrada', $quantity, $unitCost, $balanceStock, $description, $referenceType, $referenceId, $warehouseId);
    }

    public static function exit(
        int $sellerId,
        int $itemId,
        float $quantity,
        float $unitCost,
        float $balanceStock,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $warehouseId = null
    ): InvKardex {
        return self::create($sellerId, $itemId, 'salida', $quantity, $unitCost, $balanceStock, $description, $referenceType, $referenceId, $warehouseId);
    }
}
