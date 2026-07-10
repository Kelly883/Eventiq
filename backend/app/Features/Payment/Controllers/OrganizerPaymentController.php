<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Services\PayoutService;
use Illuminate\Http\Request;

class OrganizerPaymentController
{
    public function updateSettings(Request $request, PayoutService $payoutService)
    {
        // TODO: update organizer payout/payment settings.
        return response()->json(['status' => 'ok']);
    }
}

