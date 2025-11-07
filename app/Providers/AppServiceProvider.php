<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFour();

        view()->composer('*', function ($view) {
            $outlet = \App\Support\OutletContext::currentOutlet();
            $role = \App\Support\OutletContext::currentRole();
            $user = auth()->user();

            $canManageExpense = (bool) optional($role)->can_manage_expense;

            if ($user && $role && $role->role === 'partner' && $outlet) {
                /** @var \App\Services\PartnerCategoryAccessService $access */
                $access = app(\App\Services\PartnerCategoryAccessService::class);
                $categoryIds = $access->accessibleCategoryIdsFor($user, $outlet);
                if ($categoryIds === ['*'] || ! empty($categoryIds)) {
                    $canManageExpense = true;
                }
            }

            $permissions = [
                'can_manage_stock' => (bool) optional($role)->can_manage_stock,
                'can_manage_expense' => $canManageExpense,
                'can_manage_sales' => (bool) optional($role)->can_manage_sales,
            ];

            $view->with([
                'currentOutlet' => $outlet,
                'currentOutletRole' => $role,
                'outletPermissions' => $permissions,
            ]);
        });
    }
}
