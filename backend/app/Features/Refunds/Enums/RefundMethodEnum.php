<?php

namespace App\Features\Refunds\Enums;

enum RefundMethodEnum: string
{
    case ORIGINAL_PAYMENT_METHOD = 'original_payment_method';
    case STORE_CREDIT = 'store_credit';
    case ALTERNATIVE_PAYMENT_METHOD = 'alternative_payment_method';
}
