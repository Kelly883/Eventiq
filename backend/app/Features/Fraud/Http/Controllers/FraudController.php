<?php

namespace App\Features\Fraud\Http\Controllers;

use App\Features\Fraud\Http\Requests\FraudCheckRequest;
use App\Features\Fraud\Services\FraudDetectionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FraudController extends Controller
{
    public function __construct(private FraudDetectionService $fraudDetection)
    {
    }

    /**
     * POST /api/fraud/detect - runs the full risk assessment
     * (Sift score + velocity + card testing) for a transaction.
     */
    public function detect(FraudCheckRequest $request)
    {
        $result = $this->fraudDetection->detectFraudRisk($request->validated());

        return response()->json($result);
    }

    /**
     * GET /api/fraud/transactions/paystack/{reference} - verify status
     * server-side (never trust a client-reported "payment succeeded").
     */
    public function verifyPaystack(string $reference)
    {
        $data = $this->fraudDetection->verifyPaystackTransaction($reference);

        return response()->json($data);
    }

    public function verifyFlutterwave(string $transactionId)
    {
        $data = $this->fraudDetection->verifyFlutterwaveTransaction($transactionId);

        return response()->json($data);
    }

    public function velocity(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(
            $this->fraudDetection->checkVelocity($validated['user_id'], $validated['amount'])
        );
    }

    public function duplicateTickets(Request $request)
    {
        $validated = $request->validate([
            'ticket_tier_id' => ['required', 'integer'],
            'qr_code' => ['required', 'string'],
        ]);

        return response()->json(
            $this->fraudDetection->detectDuplicateTickets($validated['ticket_tier_id'], $validated['qr_code'])
        );
    }
}
