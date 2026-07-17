<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Features\EmailNotifications\Policies\EmailTemplatePolicy;
use App\Features\OrganizerProfile\Models\OrganizerProfile;
use App\Features\OrganizerProfile\Policies\OrganizerProfilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        OrganizerProfile::class => OrganizerProfilePolicy::class,
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
