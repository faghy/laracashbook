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

        // Totali, dashboard, categorie, export, eliminazione definitiva e gestione utenti
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Entità e tutti gli utenti
        Gate::define('superadmin', fn (User $user) => $user->isSuperadmin());

        Gate::define('manage-user', fn (User $user, User $target) => $user->canManage($target));
    }
}
