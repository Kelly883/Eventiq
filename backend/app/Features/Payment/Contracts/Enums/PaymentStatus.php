<?php

namespace App\Features\Payment\Contracts\Enums;

/**
 * Canonical end-user payment verification status.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
}

