<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Services\PaymentService;
use Illuminate\Http\Request;

class TransactionController
{
    public function history(Request $request, PaymentService $paymentService)
    {
        // TODO: return transaction history for current user.
        return response()->json(['data' => []]);
    }
}

