<?php

namespace App\Features\Compliance\Enums;

enum AuditLogStatus: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
    case WARNING = 'warning';
    case PENDING = 'pending';
}
