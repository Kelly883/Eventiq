<?php

namespace App\Features\Payouts\Policies;

use App\Models\User;
use App\Features\Payouts\Models\Payout;
use App\Features\Payouts\Models\SettlementPolicy;
use Illuminate\Auth\Access\Response;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('organizer') || $user->hasRole('admin');
    }

    public function view(User $user, Payout $payout): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('organizer') && $payout->organizer_id === $user->organizer_id;
    }

    public function viewAnyAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function viewAdmin(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function process(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SettlementPolicy $policy): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin') && $payout->isPending();
    }
}
