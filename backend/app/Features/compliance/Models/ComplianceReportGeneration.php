<?php

namespace App\Features\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceReportGeneration extends Model
{
    use HasFactory;

    protected $table = 'compliance_report_generations';

    protected $fillable = [
        'report_code',
        'status',
        'requested_by',
        'filters',
        'result_location',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
    ];
}

