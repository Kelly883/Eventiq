<?php

namespace App\Features\Compliance\Enums;

enum AuditLogTargetType: string
{
    case USER = 'user';
    case EVENT = 'event';
    case ORDER = 'order';
    case PAYOUT = 'payout';
    case REFUND = 'refund';
    case PAYMENT = 'payment';
    case SETTING = 'setting';
    case TICKET = 'ticket';
}
