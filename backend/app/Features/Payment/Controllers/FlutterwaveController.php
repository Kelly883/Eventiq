<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Services\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlutterwaveController
{
    public function __construct(private FlutterwaveService $flutterwave)
    {
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('verif-hash', '');

        if (! $this->flutterwave->verifyWebhookSignature($signature)) {
            Log::warning('Flutterwave webhook rejected: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // TODO: dispatch a queued job to process the event (idempotently,
        // keyed on tx_ref, to handle duplicate delivery) rather than
        // processing inline in the webhook request.

        return response()->json(['received' => true]);
    }
}
