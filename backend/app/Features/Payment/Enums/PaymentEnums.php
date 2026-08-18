<?php

namespace App\Features\Payment\Enums;

enum PaymentGateway: string
{
    case PAYSTACK = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
}

enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case EXPIRED = 'expired';
    case REVERSED = 'reversed';
}

enum PaymentMethodType: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case USSD = 'ussd';
    case QR = 'qr';
    case MOBILE_MONEY = 'mobile_money';
}
