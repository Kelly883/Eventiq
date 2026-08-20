<?php

namespace App\Features\Payment\Contracts;

/**
 * Shared contract both PaystackService and FlutterwaveService implement.
 * Adding a third gateway later means: implement this interface, add one
 * case to PaymentGatewayService::resolveGateway() - no other changes.
 */
interface PaymentGatewayContract
{
    /**
     * @param array $data Expected keys: email, amount, reference,
     *   callback_url, metadata
     */
    public function initializeTransaction(array $data);

    public function verifyTransaction(string $reference);

    public function refund(string $transactionId, float $amount = null, string $reason = '');

    /**
     * Initiate a bank transfer (organizer payout).
     *
     * @param array $bankDetails Expected keys: account_number, bank_code,
     *   account_name (account_name only required by Paystack's transfer-
     *   recipient step; Flutterwave doesn't need it)
     */
    public function transfer(float $amount, array $bankDetails, string $narration, string $reference): array;

    public function checkTransferStatus(string $transferReference): array;
}
