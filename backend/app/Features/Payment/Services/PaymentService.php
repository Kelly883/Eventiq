<?php

namespace App\Features\Payment\Services;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Contracts\PaymentGatewayContract;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use InvalidArgumentException;

class PaymentService
{
    /**
     * @var array<string, PaymentGatewayContract>
     */
    protected array $gateways;

    public function __construct(array $gateways = [])
    {
        $this->gateways = $gateways;
    }

    public function initialize(string $gateway, array $data): array
    {
        $service = $this->resolveGateway($gateway);

        return $service->initializeTransaction($data);
    }

    public function confirmPayment(string $gateway, string $reference, Order $order, Payment $payment): Payment
    {
        $service = $this->resolveGateway($gateway);

        $verification = $service->verifyTransaction($reference);

        $rawStatus = (string) data_get($verification, 'data.status', data_get($verification, 'status', ''));
        $verifiedAmount = (float) data_get($verification, 'data.amount', data_get($verification, 'amount', 0));

        // For Paystack, amount is in kobo, need to convert
        if ($gateway === PaymentGateway::PAYSTACK->value) {
            $verifiedAmount = $verifiedAmount / 100;
        }

        $status = match ($rawStatus) {
            'success', 'successful' => PaymentStatus::SUCCESS,
            'failed' => PaymentStatus::FAILED,
            'abandoned' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };

        $amountMatches = abs($verifiedAmount - $payment->amount) < 0.01;
        $currencyMatches = strtoupper((string) data_get($verification, 'data.currency', data_get($verification, 'currency', ''))) === strtoupper($payment->currency);

        if (! $amountMatches || ! $currencyMatches) {
            $status = PaymentStatus::FAILED;
        }

        $payment->update([
            'status' => $status->value,
            'gateway_response' => $verification,
            'paid_at' => $status === PaymentStatus::SUCCESS ? now() : null,
        ]);

        $order->update([
            'status' => $status === PaymentStatus::SUCCESS ? 'completed' : ($status === PaymentStatus::CANCELLED ? 'abandoned' : 'failed'),
        ]);

        return $payment->fresh();
    }

    protected function resolveGateway(string $gateway): PaymentGatewayContract
    {
        if (! isset($this->gateways[$gateway])) {
            throw new InvalidArgumentException("Unsupported gateway: {$gateway}");
        }

        return $this->gateways[$gateway];
    }
}