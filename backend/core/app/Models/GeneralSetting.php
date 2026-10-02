<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $casts = [
        'mail_config'             => 'object',
        'sms_config'              => 'object',
        'global_shortcodes'       => 'object',
        'socialite_credentials'   => 'object',
        'firebase_config'         => 'object',
        'pusher_config'           => 'object',
        'operating_country'       => 'object',
        'tips_suggest_amount'     => 'array',
        'kv'                      => 'integer',
        'ev'                      => 'integer',
        'en'                      => 'integer',
        'sv'                      => 'integer',
        'sn'                      => 'integer',
        'pn'                      => 'integer',
        'force_ssl'               => 'integer',
        'in_app_payment'          => 'integer',
        'maintenance_mode'        => 'integer',
        'secure_password'         => 'integer',
        'agree'                   => 'integer',
        'multi_language'          => 'integer',
        'registration'            => 'integer',
        'system_customized'       => 'integer',
        'paginate_number'         => 'integer',
        'currency_format'         => 'integer',
        'allow_precision'         => 'integer',
        'user_cancellation_limit' => 'integer',
        'driver_registration'     => 'integer',
        'google_login'            => 'integer',
        'apple_login'             => 'integer',
        'ride_cancel_time'        => 'integer',
        'min_distance'            => 'double',
        'delivery_min_fee'        => 'double',
        'delivery_fee_per_km'     => 'double',
        'delivery_base_km'        => 'double',
        'delivery_time_rate'      => 'double',
        'delivery_avg_speed'      => 'double',
        'delivery_surge'          => 'double',
        'delivery_coverage_radius' => 'double',
        'delivery_convenient_fee_limit' => 'double',
        'favor_min_fee'            => 'double',
        'favor_fee_per_km'         => 'double',
        'favor_base_km'            => 'double',
        'favor_time_rate'          => 'double',
        'favor_avg_speed'          => 'double',
        'favor_surge'              => 'double',
        'favor_coverage_radius'    => 'double',
        'negative_balance_driver'  => 'double',
        'min_driver_recharge'      => 'double',
        'initial_promotional_credit' => 'double',
        'driver_base_fare'         => 'double',
        'driver_rate_per_km'       => 'double',
        'driver_rate_per_minute'   => 'double',
        'batch_double_bonus'       => 'double',
        'batch_triplet_bonus'      => 'double',
        'batch_quad_bonus'         => 'double',
        'max_batch_pickup_distance_km' => 'double',
        'max_batch_detour_km'      => 'double',
        'max_batch_detour_minutes' => 'double',
        'delivery_sections_config' => 'array',
    ];

    protected $hidden = ['email_template', 'mail_config', 'sms_config', 'system_info'];

    public function siteName($pageTitle = '')
    {
        $pageTitle = empty($pageTitle) ? '' : ' - ' . $pageTitle;
        return $this->site_name . $pageTitle;
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function () {
            \Cache::forget('GeneralSetting');
        });
    }

    public function distanceUnitName(): Attribute
    {
        return new Attribute(function () {
            return $this->distance_unit == Status::MILE_UNIT ? 'MILE' : 'KM';
        });
    }
}
