<?php

namespace App\Features\Compliance\Enums;

enum ReportFormat: string
{
    case PDF = 'pdf';
    case CSV = 'csv';
    case JSON = 'json';
}
