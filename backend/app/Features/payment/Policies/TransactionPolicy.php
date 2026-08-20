<?php

namespace App\Features\Payment\Policies;

use App\Features\Payment\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return (string) $transaction->user_id === (string) $user->id
            || (string) $transaction->organizer_id === (string) $user->organizer?->id;
    }

    public function viewForOrganizer(User $user, Transaction $transaction): bool
    {
        return (string) $transaction->organizer_id === (string) $user->organizer?->id;
    }
}
