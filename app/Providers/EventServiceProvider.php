<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\OrderPaid::class => [
            \App\Listeners\ConsumeRawMaterialsOnOrderPaid::class,
        ],
        \App\Events\PartnerInvitationCreated::class => [
            \App\Listeners\SendPartnerInvitationEmail::class,
        ],
        \App\Events\PartnerCategoryRequestCreated::class => [
            \App\Listeners\NotifyOwnersOfCategoryRequest::class,
        ],
        \App\Events\PartnerCategoryRequestApproved::class => [
            \App\Listeners\SendPartnerCategoryDecisionEmail::class,
        ],
        \App\Events\PartnerCategoryRequestRejected::class => [
            \App\Listeners\SendPartnerCategoryDecisionEmail::class,
        ],
        // Inventory/production module removed: disable raw material consumption on order paid
        // \App\Events\OrderPaid::class => [
        //     \App\Listeners\ConsumeRawMaterialsOnOrderPaid::class,
        // ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
