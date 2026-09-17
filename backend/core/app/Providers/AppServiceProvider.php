<?php

namespace App\Providers;


use App\Lib\Searchable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!class_exists(\Predis\Client::class) && file_exists(base_path('vendor/predis/predis/autoload.php'))) {
            require_once base_path('vendor/predis/predis/autoload.php');
        }

        Builder::mixin(new Searchable);
        Builder::macro("firstOrFailWithApi", function ($modelName) {
            $data = $this->first();
            if (!$data) {
                throw new \Exception("custom_not_found_exception || The $modelName is not found", 404);
            }
            return $data;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Event::listen(
            \App\Events\NewDeliveryOrderPlaced::class,
            \App\Listeners\SendWhatsAppOrderNotification::class
        );
    }
}
