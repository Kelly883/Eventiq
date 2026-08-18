<?php

namespace App\Features\Compliance\Enums;

use Illuminate\Support\Enum;

enum AuditLogAction: string
{
    case USER_LOGIN = 'user_login';
    case USER_LOGOUT = 'user_logout';
    case USER_SUSPENDED = 'user_suspended';
    case EVENT_CREATED = 'event_created';
    case EVENT_APPROVED = 'event_approved';
    case EVENT_FLAGGED = 'event_flagged';
    case EVENT_CANCELLED = 'event_cancelled';
    case PAYMENT_PROCESSED = 'payment_processed';
    case PAYMENT_REFUNDED = 'payment_refunded';
    case REFUND_REQUESTED = 'refund_requested';
    case REFUND_APPROVED = 'refund_approved';
    case REFUND_REJECTED = 'refund_rejected';
    case PAYOUT_APPROVED = 'payout_approved';
    case PAYOUT_REJECTED = 'payout_rejected';
    case TICKET_CHECKED_IN = 'ticket_checked_in';
    case TICKET_VOIDED = 'ticket_voided';
    case FRAUD_FLAGGED = 'fraud_flagged';
    case FRAUD_APPROVED = 'fraud_approved';
    case ADMIN_SETTING_CHANGED = 'admin_setting_changed';
    case USER_PERMISSION_CHANGED = 'user_permission_changed';
    case DATA_EXPORT_REQUESTED = 'data_export_requested';
}

enum AuditLogTargetType: string
{
    case USER = 'user';
    case EVENT = 'event';
    case ORDER = 'order';
    case PAYOUT = 'payout';
    case REFUND = 'refund';
    case PAYMENT = 'payment';
    case SETTING = 'setting';
}

enum AuditLogStatus: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
    case WARNING = 'warning';
    case PENDING = 'pending';
}

enum ComplianceClassification: string
{
    case PUBLIC = 'public';
    case INTERNAL = 'internal';
    case CONFIDENTIAL = 'confidential';
    case RESTRICTED = 'restricted';
}
