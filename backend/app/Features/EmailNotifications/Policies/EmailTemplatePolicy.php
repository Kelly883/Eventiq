<?php

namespace App\Features\EmailNotifications\Policies;

use App\Models\User;
use App\Features\EmailNotifications\Models\EmailTemplate;

class EmailTemplatePolicy
{
    // Platform-wide email templates are an admin-only resource: any user who
    // can edit them can inject content into the platform mail channel.
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->hasRole('admin');
    }
}
