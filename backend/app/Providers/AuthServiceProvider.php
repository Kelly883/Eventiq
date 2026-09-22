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
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Illuminate\Foundation\Support\Providers\AuthServiceProvider normally
        // calls registerPolicies() from its own register() method, but register()
        // below overrides that hook with an empty body. Without this explicit
        // call every entry in $policies is inert and Gate silently falls back to
        // convention-based auto-discovery, which:
        //   - denies access whenever the policy is not at the conventional path
        //     (e.g. App\Features\Tickets\Policies\TicketPolicy), and
        //   - silently ignores any deliberately stricter mapping in $policies,
        //     which is a fail-open security hazard.
        $this->registerPolicies();
    }
}
