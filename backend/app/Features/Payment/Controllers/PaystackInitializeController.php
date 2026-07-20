<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Requests\InitializePaymentRequest;
use App\Features\Payment\Services\PaystackService;

class PaystackInitializeController
{
    public function __construct(private PaystackService $paystack)
    {
    }

    public function __invoke(InitializePaymentRequest $request)
    {
        $data = $request->validated();

        $init = $this->paystack->initializeTransaction([
            'email' => $data['email'],
            'amount' => (float) $data['amount'],
            'reference' => $data['reference'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'currency' => $data['currency'] ?? null,
            'customizations' => $data['customizations'] ?? null,
        ]);

        return response()->json([
            'data' => $init,
        ]);
    }
}

