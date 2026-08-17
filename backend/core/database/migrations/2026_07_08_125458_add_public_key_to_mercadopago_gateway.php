<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $gateway = DB::table('gateways')->where('alias', 'MercadoPago')->first();
        if ($gateway) {
            $params = json_decode($gateway->gateway_parameters, true) ?? [];
            if (!isset($params['public_key'])) {
                $params['public_key'] = [
                    'title' => 'Public Key',
                    'global' => true,
                    'value' => '--------------'
                ];
                DB::table('gateways')
                    ->where('alias', 'MercadoPago')
                    ->update(['gateway_parameters' => json_encode($params)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $gateway = DB::table('gateways')->where('alias', 'MercadoPago')->first();
        if ($gateway) {
            $params = json_decode($gateway->gateway_parameters, true) ?? [];
            if (isset($params['public_key'])) {
                unset($params['public_key']);
                DB::table('gateways')
                    ->where('alias', 'MercadoPago')
                    ->update(['gateway_parameters' => json_encode($params)]);
            }
        }
    }
};
