<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Defincion de Gates para control de acceso (RBAC)
        Gate::define('gestionar-clientes', function (User $user) {
            return $user->hasRole('admin', 'operador');
        });

        Gate::define('gestionar-declaraciones', function (User $user) {
            return $user->hasRole('admin', 'operador');
        });

        Gate::define('auditar-sistema', function (User $user) {
            return $user->hasRole('admin', 'auditor');
        });

        Gate::define('administrar-usuarios', function (User $user) {
            return $user->isAdmin();
        });
    }
}
