<?php

namespace App\Features\Payment\Controllers;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Jobs\SendPaymentConfirmationJob;
use App\Features\Payment\Services\FlutterwaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FlutterwaveCallbackController
{
    public function __construct(private FlutterwaveService $flutterwave)
    {
    }

    public function handle(Request $request): RedirectResponse
    {
        $transactionId = (string) $request->query('transaction_id', '');
        $txRef = (string) $request->query('tx_ref', '');

        if ($transactionId === '' || $txRef === '') {
            return redirect()->away('/?payment=error&reason=missing_reference');
        }

        $payment = Payment::query()
            ->where('gateway', PaymentGateway::FLUTTERWAVE)
            ->where('gateway_reference', $txRef)
            ->first();

        if (! $payment) {
            Log::warning('Flutterwave callback: payment not found', ['tx_ref' => $txRef]);
            return redirect()->away('/?payment=error&reason=payment_not_found');
        }

        try {
            $verification = $this->flutterwave->verifyTransaction($transactionId);
        } catch (\Throwable $e) {
            Log::error('Flutterwave callback verification failed: ' . $e->getMessage(), ['tx_ref' => $txRef]);
            return redirect()->away('/?payment=error&reason=verification_failed');
        }

        $rawStatus = (string) data_get($verification, 'data.status', data_get($verification, 'status', ''));
        $verifiedAmount = (float) data_get($verification, 'data.amount', $payment->amount);
        $verifiedCurrency = (string) data_get($verification, 'data.currency', $payment->currency);
        $verifiedTxRef = (string) data_get($verification, 'data.tx_ref', $txRef);

        if ($payment->status !== 'success') {
            $status = match ($rawStatus) {
                'successful', 'completed' => 'success',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'failed',
            };

            $isSuccess = $status === 'success'
                && $verifiedTxRef === $txRef
                && $verifiedAmount >= $payment->amount
                && strtoupper($verifiedCurrency) === strtoupper($payment->currency)
                && $verifiedAmount <= ($payment->amount * 1.05); // allow small gateway fee variance

            if (! $isSuccess) {
                Log::warning('Flutterwave callback validation failed', [
                    'tx_ref' => $txRef,
                    'expected_amount' => $payment->amount,
                    'verified_amount' => $verifiedAmount,
                    'expected_currency' => $payment->currency,
                    'verified_currency' => $verifiedCurrency,
                    'raw_status' => $rawStatus,
                ]);
                $status = 'failed';
            }

            DB::transaction(function () use ($payment, $status, $verification, $rawStatus) {
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
        $successUrl = $frontendBase . '/payment/success?tx_ref=' . $txRef;
        $failureUrl = $frontendBase . '/payment/failed?tx_ref=' . $txRef;

        $finalStatus = $payment->status;

        return redirect()->away($finalStatus === 'success' ? $successUrl : $failureUrl);
    }
}
