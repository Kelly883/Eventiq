<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Jobs\ProcessPaystackWebhookJob;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackController
{
    public function __construct(private PaystackService $paystack)
    {
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature', '');
        $rawPayload = $request->getContent();

        if (! $this->paystack->verifyWebhookSignature($rawPayload, $signature)) {
            // Log enough to investigate, never the signature/secret itself.
            Log::warning('Paystack webhook rejected: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        ProcessPaystackWebhookJob::dispatch($request->all());

        return response()->json(['received' => true]);
    }
}
