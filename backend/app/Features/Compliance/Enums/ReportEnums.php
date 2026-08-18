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

enum ReportFormat: string
{
    case PDF = 'pdf';
    case CSV = 'csv';
    case JSON = 'json';
}

enum ReportStatus: string
{
    case QUEUED = 'queued';
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
