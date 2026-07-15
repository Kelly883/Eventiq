<?php

namespace App\Features\EmailNotifications\Policies;

use App\Models\User;
use App\Features\EmailNotifications\Models\EmailTemplate;

class EmailTemplatePolicy
{
    // TODO: tighten once role/permission conventions for this project are
    // confirmed (an is_admin flag vs a role/permission system) - defaulting
    // to "any authenticated user" is deliberately permissive rather than
    // guessing at a specific role name that may not exist.
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return true;
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return true;
    }
}
