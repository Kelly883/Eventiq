<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\ApiKeyPolicy;
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
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
