<?php

namespace App\Features\Compliance\Jobs;

use App\Features\Compliance\Models\ComplianceReportGeneration;
use App\Features\Compliance\Services\ComplianceReportService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class GenerateComplianceReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $generationId,
        public string $reportCode,
        public array $filters = []
    ) {
        $this->onQueue(config('queue.compliance_queue', 'compliance'));
    }

    public function handle(ComplianceReportService $service): void
    {
        $generation = ComplianceReportGeneration::find($this->generationId);
        if (!$generation) {
            return;
        }

        $generation->status = 'processing';
        $generation->save();

        try {
            $result = $service->generate($this->reportCode, $this->filters);

            // Expecting result to include optional result_location
            $generation->status = 'ready';
            $generation->result_location = $result['result_location'] ?? null;
            $generation->error_message = null;
            $generation->save();
        } catch (\Throwable $e) {
            $generation->status = 'failed';
            $generation->error_message = $e->getMessage();
            $generation->save();
        }
    }
}


