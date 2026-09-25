<?php

namespace App\Features\Refunds\Jobs;

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Compliance\Services\AuditLogService;
use App\Services\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $refundRequestId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Prevent concurrent processing of the same refund request
        // Using pessimistic locking on the refund request record
        $refundRequest = DB::transaction(function () {
            // Lock the refund request row to prevent race conditions
            $request = RefundRequest::where('id', $this->refundRequestId)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->first();

            if (!$request) {
                // Request not found or already processed (status not approved)
                return null;
            }

            return $request;
        });

        if (!$refundRequest) {
            Log::info("ProcessRefundJob: Refund request {$this->refundRequestId} not found or already processed");
            return;
        }

        // Update status to processing
        $refundRequest->update([
            'status' => 'processing',
            'processing_started_at' => now(),
        ]);

        try {
            // Process the refund through the payment gateway
            $paymentGatewayService = app(PaymentGatewayService::class);
            $result = $paymentGatewayService->processRefund($refundRequest->id);

            // Update to completed
            DB::transaction(function () use ($refundRequest, $result) {
                $refundRequest->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'payment_gateway_refund_id' => $result['id'] ?? $result['refund_id'] ?? null,
                    'payment_gateway_response' => $result,
                ]);
            });

            $this->logAudit('refund.completed', 'Refund successfully processed through payment gateway');

        } catch (\Throwable $e) {
            Log::error("ProcessRefundJob failed for refund request {$this->refundRequestId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Update status to failed for tracking
            DB::transaction(function () use ($e) {
                RefundRequest::where('id', $this->refundRequestId)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'payment_gateway_response' => json_encode([
                            'error' => $e->getMessage(),
                            'failed_at' => now()->toDateTimeString(),
                        ]),
                    ]);
            });

            $this->logAudit('refund.failed', 'Refund processing failed: ' . $e->getMessage());

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    private function logAudit(string $action, string $message): void
    {
        $auditLogService = app(AuditLogService::class);
        $auditLogService->log($action, 'refund_request', $this->refundRequestId, [
            'message' => $message,
            'processed_at' => now()->toDateTimeString(),
        ]);
    }
}