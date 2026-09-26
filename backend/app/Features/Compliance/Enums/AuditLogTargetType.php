<?php

namespace App\Features\Compliance\Enums;

enum AuditLogTargetType: string
{
    case USER = 'user';
    case EVENT = 'event';
    case ORDER = 'order';
    case PAYOUT = 'payout';
    case REFUND = 'refund';
    case REFUND_REQUEST = 'refund_request';
    case PAYMENT = 'payment';
    case SETTING = 'setting';
    case TICKET = 'ticket';
    case PRICING_WINDOW = 'pricing_window';
    case EMAIL_TEMPLATE = 'email_template';
}
