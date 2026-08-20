<?php

namespace App\Features\Payment\Contracts\Enums;

/**
 * Gateway-agnostic transaction lifecycle state.
 */
enum TransactionState: string
{
    case INITIATED = 'initiated';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}

