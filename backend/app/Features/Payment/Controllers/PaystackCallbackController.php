<?php

namespace App\Features\Payment\Controllers;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Jobs\SendPaymentConfirmationJob;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaystackCallbackController
{
    public function __construct(private PaystackService $paystack)
    {
    }

    public function handle(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()->away('/?payment=error&reason=missing_reference');
        }

        $payment = Payment::query()
            ->where('gateway', PaymentGateway::PAYSTACK)
            ->where('gateway_reference', $reference)
            ->first();

        if (! $payment) {
            Log::warning('Paystack callback: payment not found', ['reference' => $reference]);
            return redirect()->away('/?payment=error&reason=payment_not_found');
        }

        try {
            $verification = $this->paystack->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error('Paystack callback verification failed: ' . $e->getMessage(), ['reference' => $reference]);
            return redirect()->away('/?payment=error&reason=verification_failed');
        }

        $rawStatus = (string) data_get($verification, 'data.status', data_get($verification, 'status', ''));
        $verifiedAmount = (float) data_get($verification, 'data.amount', 0) / 100; // kobo → major
        $verifiedCurrency = (string) data_get($verification, 'data.currency', $payment->currency);

        if ($payment->status !== 'success') {
            $status = match ($rawStatus) {
                'success' => 'success',
                'failed' => 'failed',
                'abandoned' => 'abandoned',
                default => 'failed',
            };

            $isSuccess = $status === 'success'
                && $verifiedAmount >= $payment->amount
                && strtoupper($verifiedCurrency) === strtoupper($payment->currency)
                && $verifiedAmount <= ($payment->amount * 1.05); // allow small gateway fee variance

            if (! $isSuccess) {
                Log::warning('Paystack callback validation failed', [
                    'reference' => $reference,
                    'expected_amount' => $payment->amount,
                    'verified_amount' => $verifiedAmount,
                    'expected_currency' => $payment->currency,
                    'verified_currency' => $verifiedCurrency,
                ]);
                $status = 'failed';
            }

            DB::transaction(function () use ($payment, $status, $verification, $verifiedAmount, $verifiedCurrency, $rawStatus) {
                $payment->update([
                    'status' => $status,
                    'gateway_response' => $verification,
                    'last_error' => $status === 'failed' ? $rawStatus : null,
                ]);

                if ($status === 'success') {
                    Order::query()->whereKey($payment->order_id)->update(['status' => 'completed']);
                    SendPaymentConfirmationJob::dispatch($payment->order_id);
                } else {
                    Order::query()->whereKey($payment->order_id)->update(['status' => 'failed']);
                }
            });
        }

        $frontendBase = rtrim(config('app.frontend_url', '/'), '/');
        $successUrl = $frontendBase . '/payment/success?reference=' . $reference;
        $failureUrl = $frontendBase . '/payment/failed?reference=' . $reference;

        $finalStatus = $payment->status;

        return redirect()->away($finalStatus === 'success' ? $successUrl : $failureUrl);
    }
}
