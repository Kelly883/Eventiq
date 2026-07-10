<?php

namespace App\Features\Payment\Policies;

use App\Models\User;

class PaymentMethodPolicy
{
    public function view(User $user): bool
    {
        return true;
    }
}

