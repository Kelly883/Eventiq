<?php

namespace App\Features\Payment\Policies;

use App\Models\Transaction as PlatformTransaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, PlatformTransaction $transaction): bool
    {
        // TODO: implement authorization.
        return true;
    }
}

