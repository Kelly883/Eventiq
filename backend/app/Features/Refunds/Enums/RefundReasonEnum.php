<?php

namespace App\Features\Refunds\Enums;

enum RefundReasonEnum: string
{
    case EVENT_CANCELLED = 'event_cancelled';
    case PERSONAL_CIRCUMSTANCES = 'personal_circumstances';
    case DUPLICATE_PURCHASE = 'duplicate_purchase';
    case OTHER = 'other';
    case PAYMENT_ISSUE = 'payment_issue';
    case POLICY_VIOLATION = 'policy_violation';
}
