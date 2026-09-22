<?php

namespace App\Features\Fraud\Http\Controllers;

use App\Features\Fraud\Models\FraudEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminFraudController extends Controller
{
    /**
     * GET /api/admin/fraud/flagged-transactions
     *
     * Returns paginated list of fraud events, optionally filtered by
     * risk_level, status, date range.
     */
    public function flaggedTransactions(Request $request)
    {
        $validated = $request->validate([
            'risk_level' => ['nullable', 'string', 'in:low,medium,high'],
            'status' => ['nullable', 'string', 'in:flagged,reviewed,approved,rejected,auto_blocked'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $perPage = $validated['per_page'] ?? 25;

        $query = FraudEvent::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if (!empty($validated['risk_level'])) {
            $query->where('risk_level', $validated['risk_level']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (!empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        return response()->json([
            'data' => $query->paginate($perPage),
            'meta' => [
                'filters' => [
                    'risk_level' => $validated['risk_level'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/fraud/transaction-details/:fraudEventId
     *
     * Returns comprehensive fraud analysis for a single fraud event.
     */
    public function transactionDetails(string $fraudEventId)
    {
        $fraudEvent = FraudEvent::with(['user:id,name,email', 'order:id,status,total_amount'])
            ->where('id', $fraudEventId)
            ->first();

        if (!$fraudEvent) {
            return response()->json(['message' => 'Fraud event not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $fraudEvent->id,
                'user_id' => $fraudEvent->user_id,
                'user' => $fraudEvent->user,
                'risk_score' => $fraudEvent->risk_score,
                'risk_level' => $fraudEvent->risk_level,
                'fraud_type' => $fraudEvent->fraud_type,
                'fraud_factors' => $fraudEvent->fraud_factors,
                'status' => $fraudEvent->status,
                'detection_method' => $fraudEvent->detection_method,
                'amount' => $fraudEvent->amount,
                'currency' => $fraudEvent->currency,
                'ip_address' => $fraudEvent->ip_address,
                'device_info' => $fraudEvent->device_info,
                'velocity_metrics' => $fraudEvent->velocity_metrics,
                'payment_details' => $fraudEvent->payment_details,
                'order' => $fraudEvent->order,
                'created_at' => $fraudEvent->created_at,
                'updated_at' => $fraudEvent->updated_at,
            ],
        ]);
    }
}
