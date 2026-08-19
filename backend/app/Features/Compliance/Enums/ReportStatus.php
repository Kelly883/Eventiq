<?php

namespace App\Features\Compliance\Enums;

enum ReportStatus: string
{
    case QUEUED = 'queued';
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
