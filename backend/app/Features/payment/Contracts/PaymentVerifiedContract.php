<?php

namespace App\Features\Payment\Contracts;

use App\Features\Payment\Contracts\Enums\PaymentGatewayName;
use App\Features\Payment\Contracts\Enums\PaymentStatus;
use App\Features\Payment\Contracts\Enums\TransactionState;

final class PaymentVerifiedContract
{
    public function __construct(
        public readonly PaymentGatewayName $gateway,
        public readonly string $reference,
        public readonly PaymentStatus $status,
        public readonly TransactionState $transactionState,
        public readonly ?array $metadata = null,
    ) {
    }
}

