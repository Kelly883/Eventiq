<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Requests\VerifyPaymentRequest;
use App\Features\Payment\Services\PaystackService;

class PaystackVerifyController
{
    public function __construct(private PaystackService $paystack)
    {
    }

    public function __invoke(VerifyPaymentRequest $request)
    {
        $data = $request->validated();

        $reference = $data['reference'];

        $verified = $this->paystack->verifyTransaction($reference);

        return response()->json([
            'data' => $verified,
        ]);
    }
}

