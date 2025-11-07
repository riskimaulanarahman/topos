<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Outlet::class => \App\Policies\OutletPolicy::class,
        \App\Models\DuplicationJob::class => \App\Policies\DuplicationJobPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Map existing user roles (admin/staff/user) to requested permissions
        Gate::define('finance.view', function ($user) {
            return in_array($user->roles, ['admin','staff','user']);
        });
        Gate::define('finance.manage', function ($user) {
            return in_array($user->roles, ['admin']); // treat admin as owner/manager
        });

        Gate::define('inventory.manage', function ($user) {
            // Izinkan admin dan user untuk modul inventory
            return in_array($user->roles, ['admin','user']);
        });

        Gate::define('employees.manage', function ($user) {
            return in_array($user->roles, ['admin']);
        });
    }
}
