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
     * (Sift score + velocity + card testing + device + IP) for a transaction.
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

    /**
     * GET /api/fraud/transactions/flutterwave/{transactionId} - verify status
     * server-side.
     */
    public function verifyFlutterwave(string $transactionId)
    {
        $data = $this->fraudDetection->verifyFlutterwaveTransaction($transactionId);

        return response()->json($data);
    }

    /**
     * POST /api/fraud/velocity - checks velocity limits
     */
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

    /**
     * POST /api/fraud/duplicate-tickets - checks for duplicate tickets
     */
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

    /**
     * POST /api/fraud/device - validates device fingerprint
     */
    public function deviceFingerprint(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string'],
        ]);

        return response()->json(
            $this->fraudDetection->checkDeviceFingerprint($validated['device_id'])
        );
    }

    /**
     * POST /api/fraud/ip - checks IP address reputation
     */
    public function ipReputation(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'string'],
        ]);

        return response()->json(
            $this->fraudDetection->checkIpReputation($validated['ip'])
        );
    }

    /**
     * GET /api/fraud/event/{id} - retrieves event/transaction details
     */
    public function eventDetails(string $id, Request $request)
    {
        return response()->json(
            $this->fraudDetection->getTransactionDetails($id, $request->get('provider'))
        );
    }
}
