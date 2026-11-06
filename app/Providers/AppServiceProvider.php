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
        // Importo in euro nel formato della lingua: @euro($importo) -> 1.234,56 € (it) oppure €1,234.56 (en)
        Blade::directive('euro', fn ($expression) => "<?php echo e(\\App\\Providers\\AppServiceProvider::euro($expression)); ?>");
    }

    public static function euro($amount): string
    {
        $amount = (float) $amount;

        if (app()->getLocale() === 'it') {
            return number_format($amount, 2, ',', '.').' €';
        }

        return ($amount < 0 ? '-' : '').'€'.number_format(abs($amount), 2, '.', ',');
    }
}
