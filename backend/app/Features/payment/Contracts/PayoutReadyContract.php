<?php

namespace App\Features\Payment\Contracts;

use App\Features\Payment\Contracts\Enums\PaymentGatewayName;

final class PayoutReadyContract
{
    public function __construct(
        public readonly PaymentGatewayName $gateway,
        public readonly string $transactionReference,
        public readonly int $organizerId,
        public readonly string $payoutReference,
        public readonly int $amountMinor,
        public readonly string $currency,
    ) {
    }
}

