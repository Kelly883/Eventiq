<?php

namespace App\Features\Payment\Enums;

enum PaymentGateway: string
{
    case PAYSTACK = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
}
