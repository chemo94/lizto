<?php

namespace App\Support;

/**
 * Lizto stores POS selling prices as tax-inclusive amounts. Keep the
 * breakdown in one place so local receipts, SUNAT payloads and inventory
 * sales use the same cent rounding rule.
 */
class TaxCalculator
{
    public const RATE = 0.18;
    public const TYPES = ['gravado', 'exonerado', 'inafecto'];

    public static function line(float $gross, string $taxType): array
    {
        if (!in_array($taxType, self::TYPES, true)) {
            throw new \InvalidArgumentException('Tipo de operación tributaria inválido.');
        }

        $gross = round($gross, 2);
        if ($taxType === 'gravado') {
            $base = round($gross / (1 + self::RATE), 2);
            return ['base' => $base, 'igv' => round($gross - $base, 2), 'total' => $gross];
        }

        return ['base' => $gross, 'igv' => 0.0, 'total' => $gross];
    }

    public static function totals(iterable $lines): array
    {
        $totals = ['gravada' => 0.0, 'exonerada' => 0.0, 'inafecta' => 0.0, 'igv' => 0.0, 'total' => 0.0];
        foreach ($lines as $line) {
            $type = $line['tax_type'];
            $parts = self::line((float) $line['total'], $type);
            $key = match ($type) {
                'gravado' => 'gravada',
                'exonerado' => 'exonerada',
                'inafecto' => 'inafecta',
            };
            $totals[$key] += $parts['base'];
            $totals['igv'] += $parts['igv'];
            $totals['total'] += $parts['total'];
        }

        foreach ($totals as $key => $value) $totals[$key] = round($value, 2);
        return $totals;
    }

    /** Build tax-ready, tax-inclusive invoice lines from a POS order snapshot. */
    public static function orderLines($order, string $deliveryTaxType = 'gravado'): array
    {
        $items = $order->items;
        $subtotal = (float) $items->sum('total_price');
        $discount = min(max(0, (float) $order->discount), $subtotal);
        $remainingDiscount = round($discount, 2);
        $lines = [];
        foreach ($items as $index => $item) {
            $gross = round((float) $item->total_price, 2);
            $allocated = $index === $items->count() - 1
                ? $remainingDiscount
                : ($subtotal > 0 ? min($remainingDiscount, round($discount * $gross / $subtotal, 2)) : 0.0);
            $remainingDiscount = round($remainingDiscount - $allocated, 2);
            $gross = max(0, round($gross - $allocated, 2));
            $quantity = max(1, (int) $item->quantity);
            $lines[] = [
                'name' => $item->product_name,
                'quantity' => $quantity,
                'unit_price' => round($gross / $quantity, 6),
                'total' => $gross,
                'tax_type' => in_array($item->tax_type, self::TYPES, true) ? $item->tax_type : 'gravado',
                'item' => $item,
            ];
        }

        $deliveryFee = round((float) $order->delivery_fee, 2);
        if ($deliveryFee > 0) {
            $lines[] = [
                'name' => 'Cargo de entrega', 'quantity' => 1,
                'unit_price' => $deliveryFee, 'total' => $deliveryFee,
                'tax_type' => in_array($deliveryTaxType, self::TYPES, true) ? $deliveryTaxType : 'gravado',
                'item' => null,
            ];
        }

        return $lines;
    }
}
