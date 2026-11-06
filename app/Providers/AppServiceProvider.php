<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Importo in formato italiano: @euro($importo) -> 1.234,56 €
        Blade::directive('euro', fn ($expression) => "<?php echo e(number_format((float) ($expression), 2, ',', '.').' €'); ?>");
    }
}
