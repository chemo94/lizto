<?php

namespace App\Services;

class CreditNoteReasons
{
    public const LABELS = [
        '01'=>'Anulación de la operación', '02'=>'Anulación por error en el RUC',
        '03'=>'Corrección por error en la descripción', '04'=>'Descuento global',
        '05'=>'Descuento por ítem', '06'=>'Devolución total', '07'=>'Devolución por ítem',
        '08'=>'Bonificación', '09'=>'Disminución en el valor', '10'=>'Otros conceptos',
        '11'=>'Ajustes de operaciones de exportación', '12'=>'Ajustes afectos al IVAP',
        '13'=>'Corrección del monto pendiente, vencimientos y cuotas',
    ];

    public static function cancelsDocument(string $reason): bool
    {
        return in_array($reason, ['01','02','06'], true);
    }
}
