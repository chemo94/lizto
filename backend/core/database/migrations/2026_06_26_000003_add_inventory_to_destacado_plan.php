<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function decodeFeatures($raw): array
    {
        if (is_array($raw)) return $raw;
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        if (is_string($decoded)) {
            $decoded2 = json_decode($decoded, true);
            if (is_array($decoded2)) return $decoded2;
        }
        return [];
    }

    public function up(): void
    {
        $destacado = DB::table('business_packages')->where('type', 'featured')->first();
        if ($destacado) {
            $features = $this->decodeFeatures($destacado->features);
            if (!in_array('Gestión de Inventario (Kardex / Compras)', $features)) {
                $features[] = 'Gestión de Inventario (Kardex / Compras)';
            }
            DB::table('business_packages')
                ->where('type', 'featured')
                ->update(['features' => json_encode($features)]);
        }

        $premium = DB::table('business_packages')->where('type', 'premium')->first();
        if ($premium) {
            $features = $this->decodeFeatures($premium->features);
            $features[] = 'Gestión de Inventario (Kardex / Compras)';
            $features = array_unique($features);
            DB::table('business_packages')
                ->where('type', 'premium')
                ->update(['features' => json_encode($features)]);
        }
    }

    public function down(): void
    {
        $destacado = DB::table('business_packages')->where('type', 'featured')->first();
        if ($destacado) {
            $features = $this->decodeFeatures($destacado->features);
            $features = array_values(array_filter($features, fn($f) => $f !== 'Gestión de Inventario (Kardex / Compras)'));
            DB::table('business_packages')
                ->where('type', 'featured')
                ->update(['features' => json_encode($features)]);
        }
    }
};
