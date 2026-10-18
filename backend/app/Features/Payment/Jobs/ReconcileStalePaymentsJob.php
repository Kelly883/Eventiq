<?php

namespace App\Features\Payment\Jobs;

use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileStalePaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $olderThanMinutes = 60,
        public ?string $gateway = null,
    ) {
        $this->onQueue(config('queue.payment_queue', 'default'));
    }

    public function handle(PaystackService $paystack, FlutterwaveService $flutterwave): void
    {
        $threshold = now()->subMinutes($this->olderThanMinutes);

        $query = Payment::query()
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::INITIATED])
            ->where('created_at', '<', $threshold);

        if ($this->gateway) {
            $query->where('gateway', $this->gateway);
        }

        $stalePayments = $query->limit(100)->get();

        foreach ($stalePayments as $payment) {
            try {
                $service = match ($payment->gateway) {
                    PaymentGateway::PAYSTACK->value => $paystack,
                    PaymentGateway::FLUTTERWAVE->value => $flutterwave,
                    default => null,
                };

                if (! $service) {
                    continue;
                }

                $reference = (string) $payment->gateway_reference;
                if ($reference === '') {
                    continue;
                }

                $verification = $service->verifyTransaction($reference);

                $rawStatus = (string) data_get($verification, 'data.status', data_get($verification, 'status', ''));
                $status = match ($payment->gateway) {
                    PaymentStatus::PENDING->value => match ($rawStatus) {
                        'success' => PaymentStatus::SUCCESS,
                        'failed' => PaymentStatus::FAILED,
                        'abandoned' => PaymentStatus::EXPIRED,
                        default => $payment->status,
                    },
                    PaymentStatus::PROCESSING->value => match ($rawStatus) {
                        'successful', 'completed' => PaymentStatus::SUCCESS,
                        'failed' => PaymentStatus::FAILED,
                        default => $payment->status,
                    },
                    default => $payment->status,
                };

                if ($status !== $payment->status) {
                    DB::transaction(function () use ($payment, $status, $verification, $rawStatus) {
                        $payment->update([
                            'status' => $status,
                            'gateway_response' => $verification,
                            'last_error' => $status === PaymentStatus::FAILED ? $rawStatus : null,
                        ]);

                        if ($status === PaymentStatus::SUCCESS && $payment->order) {
                            $payment->order->update(['status' => 'completed']);
                        } elseif ($status === PaymentStatus::FAILED && $payment->order) {
                            $payment->order->update(['status' => 'failed']);
                        }
                    });

                    Log::info('ReconcileStalePaymentsJob updated payment', [
                        'payment_id' => $payment->id,
                        'gateway' => $payment->gateway,
                        'old_status' => $payment->status,
                        'new_status' => $status->value,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('ReconcileStalePaymentsJob failed for payment ' . $payment->id . ': ' . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'reference' => $payment->gateway_reference,
                ]);
            }
        }
    }
}
