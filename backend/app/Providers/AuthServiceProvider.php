<?php

namespace App\Providers;

use App\Features\Pricing\Models\PricingWindow;
use App\Features\Pricing\Policies\PricingWindowPolicy;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\ApiKeyPolicy;
use App\Policies\EventPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\WebhookPolicy;
use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Features\EmailNotifications\Policies\EmailTemplatePolicy;
use App\Features\OrganizerProfile\Models\OrganizerProfile;
use App\Features\OrganizerProfile\Policies\OrganizerProfilePolicy;
use App\Features\admin\Policies\AdminPolicy;
use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Policies\RefundRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => AdminPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        OrganizerProfile::class => OrganizerProfilePolicy::class,
        \App\Models\Organizer::class => \App\Features\OrganizerProfile\Policies\OrganizerProfilePolicy::class,
        \App\Models\Event::class => \App\Policies\EventPolicy::class,
        ApiKey::class => ApiKeyPolicy::class,
        Webhook::class => WebhookPolicy::class,
        PricingWindow::class => PricingWindowPolicy::class,
        \App\Features\Checkout\Models\Ticket::class => \App\Features\Tickets\Policies\TicketPolicy::class,
        RefundRequest::class => RefundRequestPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });
    }
}
