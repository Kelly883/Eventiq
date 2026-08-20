<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentMethodController
{
    public function index(Request $request, PaymentService $paymentService)
    {
        // TODO: list supported payment methods for selected gateway.
        return response()->json([]);
    }
}

