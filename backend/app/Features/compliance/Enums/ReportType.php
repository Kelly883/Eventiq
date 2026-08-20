<?php

namespace App\Features\Compliance\Enums;

enum ReportType: string
{
    case DATA_ACCESS = 'data_access';
    case USER_ACTIVITY = 'user_activity';
    case PAYMENT_AUDIT = 'payment_audit';
    case REFUND_AUDIT = 'refund_audit';
    case DATA_RETENTION = 'data_retention';
    case INCIDENT_REPORT = 'incident_report';
}
