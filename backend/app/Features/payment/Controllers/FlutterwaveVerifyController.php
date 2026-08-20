<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Requests\VerifyPaymentRequest;
use App\Features\Payment\Services\FlutterwaveService;

class FlutterwaveVerifyController
{
    public function __construct(private FlutterwaveService $flutterwave)
    {
    }

    /**
     * Verify a Flutterwave payment by transaction id.
     */
    public function __invoke(VerifyPaymentRequest $request)
    {
        $data = $request->validated();

        $transactionId = $data['reference'];

        $verified = $this->flutterwave->verifyTransaction($transactionId);

        return response()->json([
            'data' => $verified,
        ]);
    }
}

