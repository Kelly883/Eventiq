<?php

namespace App\Features\Payment\Contracts;

use App\Features\Payment\Contracts\Enums\PaymentGatewayName;

final class VerifyPaymentContract
{
    public function __construct(
        public readonly PaymentGatewayName $gateway,
        public readonly string $reference,
    ) {
    }
}

