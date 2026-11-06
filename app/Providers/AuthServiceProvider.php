<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Movimento;
use App\Models\User;
use App\Policies\MovimentoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Movimento::class => MovimentoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('view-customer', function (User $user) {
            return $user->role == 'stagista';
        });

        Gate::define('edit-customer', function (User $user) {
            return $user->role == 'operatore';
        });

        Gate::define('delete-customer', function (User $user) {
            return $user->role == 'responsabile';
        });
    }
}
