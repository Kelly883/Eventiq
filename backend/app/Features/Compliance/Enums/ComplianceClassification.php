<?php

namespace App\Features\Compliance\Enums;

enum ComplianceClassification: string
{
    case PUBLIC = 'public';
    case INTERNAL = 'internal';
    case CONFIDENTIAL = 'confidential';
    case RESTRICTED = 'restricted';
}
