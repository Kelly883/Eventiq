<?php

namespace App\Features\Payment\Policies;

use App\Features\Payment\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return (string) $paymentMethod->user_id === (string) $user->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return (string) $paymentMethod->user_id === (string) $user->id;
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return (string) $paymentMethod->user_id === (string) $user->id;
    }

    public function setDefault(User $user, PaymentMethod $paymentMethod): bool
    {
        return (string) $paymentMethod->user_id === (string) $user->id;
    }
}
