<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_templates')->insert([
            [
                'act'                    => 'CHAT_MESSAGE',
                'name'                   => 'Nuevo Mensaje de Chat',
                'subject'                => 'Nuevo mensaje de {{sender}}',
                'push_title'             => 'Nuevo mensaje de {{sender}}',
                'push_body'              => '{{sender}}: {{message}}',
                'push_status'            => 1,
                'email_status'           => 0,
                'sms_status'             => 0,
                'shortcodes'             => json_encode([
                    'ride_id' => 'ID del viaje',
                    'sender'  => 'Nombre del remitente',
                    'message' => 'Contenido del mensaje',
                ]),
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'act'                    => 'RIDE_END',
                'name'                   => 'Viaje Finalizado',
                'subject'                => 'Tu viaje #{{ride_id}} ha finalizado',
                'push_title'             => 'Viaje Finalizado',
                'push_body'              => 'Tu viaje {{service}} de {{pickup_location}} a {{destination}} ha finalizado. Monto: {{currency_symbol}}{{amount}}',
                'push_status'            => 1,
                'email_status'           => 0,
                'sms_status'             => 0,
                'shortcodes'             => json_encode([
                    'ride_id'         => 'ID del viaje',
                    'amount'          => 'Monto',
                    'service'         => 'Servicio',
                    'pickup_location' => 'Recogida',
                    'destination'     => 'Destino',
                    'duration'        => 'Duración',
                    'distance'        => 'Distancia',
                ]),
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'act'                    => 'CASH_PAYMENT_REQUEST',
                'name'                   => 'Solicitud de Pago en Efectivo',
                'subject'                => 'Pago en efectivo - Viaje #{{ride_id}}',
                'push_title'             => 'Pago en Efectivo Solicitado',
                'push_body'              => 'El pasajero pagará {{currency_symbol}}{{amount}} en efectivo. Viaje de {{pickup_location}} a {{destination}}',
                'push_status'            => 1,
                'email_status'           => 0,
                'sms_status'             => 0,
                'shortcodes'             => json_encode([
                    'ride_id'         => 'ID del viaje',
                    'amount'          => 'Monto',
                    'service'         => 'Servicio',
                    'pickup_location' => 'Recogida',
                    'destination'     => 'Destino',
                    'duration'        => 'Duración',
                    'distance'        => 'Distancia',
                ]),
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'act'                    => 'CASH_PAYMENT_RECEIVED',
                'name'                   => 'Pago en Efectivo Recibido',
                'subject'                => 'Pago recibido - Viaje #{{ride_id}}',
                'push_title'             => 'Pago Recibido',
                'push_body'              => 'El conductor confirmó tu pago de {{currency_symbol}}{{amount}} por el viaje de {{pickup_location}} a {{destination}}',
                'push_status'            => 1,
                'email_status'           => 0,
                'sms_status'             => 0,
                'shortcodes'             => json_encode([
                    'ride_id'         => 'ID del viaje',
                    'amount'          => 'Monto',
                    'service'         => 'Servicio',
                    'pickup_location' => 'Recogida',
                    'destination'     => 'Destino',
                    'duration'        => 'Duración',
                    'distance'        => 'Distancia',
                ]),
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'act'                    => 'NEW_BID',
                'name'                   => 'Nueva Oferta de Conductor',
                'subject'                => 'Nueva oferta - Viaje #{{ride_id}}',
                'push_title'             => 'Nueva Oferta Recibida',
                'push_body'              => '{{driver_name}} ofrece {{currency_symbol}}{{amount}} por tu viaje de {{pickup_location}} a {{destination}}',
                'push_status'            => 1,
                'email_status'           => 0,
                'sms_status'             => 0,
                'shortcodes'             => json_encode([
                    'ride_id'         => 'ID del viaje',
                    'amount'          => 'Monto ofertado',
                    'driver_name'     => 'Nombre del conductor',
                    'service'         => 'Servicio',
                    'pickup_location' => 'Recogida',
                    'destination'     => 'Destino',
                    'duration'        => 'Duración',
                    'distance'        => 'Distancia',
                ]),
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')->whereIn('act', [
            'CHAT_MESSAGE', 'RIDE_END', 'CASH_PAYMENT_REQUEST', 'CASH_PAYMENT_RECEIVED', 'NEW_BID',
        ])->delete();
    }
};
