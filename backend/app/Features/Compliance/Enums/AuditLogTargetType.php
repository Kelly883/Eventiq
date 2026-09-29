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
    case AUDIT_LOG = 'audit_log';
    case COMPLIANCE_REPORT_GENERATION = 'compliance_report_generation';
    case DASHBOARD = 'dashboard';
    case ACCESSIBILITY_PREFERENCE = 'accessibility_preference';
    case LANGUAGE_PREFERENCE = 'language_preference';
}
