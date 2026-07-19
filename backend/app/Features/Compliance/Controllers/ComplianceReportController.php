<?php

namespace App\Features\Compliance\Controllers;

use App\Features\Compliance\Jobs\GenerateComplianceReportJob;
use App\Features\Compliance\Requests\GenerateComplianceReportRequest;
use App\Features\Compliance\Services\AuditLogService;
use App\Features\Compliance\Services\ComplianceReportService;
use App\Http\Controllers\Controller;
use App\Features\Compliance\Models\ComplianceReportGeneration;
use Illuminate\Http\Request;

class ComplianceReportController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    public function index(Request $request)
    {
        // TODO: list available reports
        return response()->json([
            'reports' => [],
        ]);
    }

    public function generate(GenerateComplianceReportRequest $request)
    {
        $validated = $request->validated();
        $reportCode = $validated['reportCode'];
        $filters = $validated['filters'] ?? [];

        $generation = ComplianceReportGeneration::create([
            'report_code' => $reportCode,
            'status' => 'queued',
            'requested_by' => $request->user()?->id,
            'filters' => $filters,
        ]);

        GenerateComplianceReportJob::dispatch($generation->id, $reportCode, $filters);

        $this->auditLogService->log('compliance_report.queued', 'compliance_report_generation', $generation->id, [
            'report_code' => $reportCode,
            'filters' => $filters,
        ], $request->user()?->id);

        return response()->json([
            'id' => $generation->id,
            'status' => $generation->status,
        ]);
    }
}

