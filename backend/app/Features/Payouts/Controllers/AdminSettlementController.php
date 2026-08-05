<?php

namespace App\Features\Payouts\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Payouts\Models\Payout;
use App\Features\Payouts\Models\SettlementPolicy;
use App\Features\Payouts\Models\PayoutCalculation;
use App\Features\Payouts\Requests\StorePayoutRequest;
use App\Features\Payouts\Requests\UpdateSettlementPolicyRequest;
use App\Features\Payouts\Requests\ProcessPayoutRequest;
use App\Features\Payouts\Resources\PayoutResource;
use App\Features\Payouts\Resources\SettlementPolicyResource;
use App\Features\Payouts\Resources\PayoutCalculationResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminSettlementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAnyAdmin', Payout::class);

        $query = Payout::with(['calculation', 'event', 'organizer', 'settlementPolicy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organizer_id')) {
            $query->where('organizer_id', $request->organizer_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payouts = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return PayoutResource::collection($payouts);
    }

    public function show(Payout $payout)
    {
        $this->authorize('viewAdmin', $payout);

        $payout->load(['calculation', 'event', 'organizer', 'settlementPolicy']);

        return new PayoutResource($payout);
    }

    public function summary(Request $request)
    {
        $this->authorize('viewAnyAdmin', Payout::class);

        $totalSettled = Payout::where('status', Payout::STATUS_COMPLETED)
            ->sum('amount');

        $totalPending = Payout::where('status', Payout::STATUS_PENDING)
            ->sum('amount');

        $totalProcessing = Payout::where('status', Payout::STATUS_PROCESSING)
            ->sum('amount');

        $totalPlatformFee = PayoutCalculation::query()
            ->join('payouts', 'payout_calculations.payout_id', '=', 'payouts.id')
            ->where('payouts.status', Payout::STATUS_COMPLETED)
            ->sum('payout_calculations.platform_fee');

        $payoutCounts = Payout::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'total_settled' => (float) $totalSettled,
            'total_pending' => (float) $totalPending,
            'total_processing' => (float) $totalProcessing,
            'total_platform_fee' => (float) $totalPlatformFee,
            'payout_counts' => [
                'pending' => $payoutCounts[Payout::STATUS_PENDING] ?? 0,
                'processing' => $payoutCounts[Payout::STATUS_PROCESSING] ?? 0,
                'completed' => $payoutCounts[Payout::STATUS_COMPLETED] ?? 0,
                'failed' => $payoutCounts[Payout::STATUS_FAILED] ?? 0,
                'cancelled' => $payoutCounts[Payout::STATUS_CANCELLED] ?? 0,
            ],
        ]);
    }

    public function store(StorePayoutRequest $request)
    {
        $this->authorize('create', Payout::class);

        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $payout = Payout::create([
                'organizer_id' => $validated['organizer_id'],
                'event_id' => $validated['event_id'],
                'settlement_policy_id' => $validated['settlement_policy_id'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => Payout::STATUS_PENDING,
                'payout_method' => $validated['payout_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (isset($validated['calculation'])) {
                PayoutCalculation::create([
                    'payout_id' => $payout->id,
                    'event_id' => $validated['event_id'],
                    'total_revenue' => $validated['calculation']['total_revenue'],
                    'platform_fee' => $validated['calculation']['platform_fee'],
                    'organizer_share' => $validated['calculation']['organizer_share'],
                    'tax_amount' => $validated['calculation']['tax_amount'] ?? 0,
                    'refund_amount' => $validated['calculation']['refund_amount'] ?? 0,
                    'breakdown' => $validated['calculation']['breakdown'] ?? null,
                ]);
            }

            $payout->load(['calculation', 'event', 'organizer', 'settlementPolicy']);

            return new PayoutResource($payout);
        });
    }

    public function processPayout(ProcessPayoutRequest $request, Payout $payout)
    {
        $this->authorize('process', $payout);

        if (!$payout->isPending()) {
            throw ValidationException::withMessages([
                'payout' => ['Only pending payouts can be processed.'],
            ]);
        }

        return DB::transaction(function () use ($request, $payout) {
            $validated = $request->validated();

            $payout->markAsProcessing();

            // In a real application, this would integrate with a payment processor
            // For now, we'll simulate processing and mark as completed
            $transactionId = $validated['transaction_id'] ?? 'txn_' . uniqid();
            $payout->markAsCompleted($transactionId);
            $payout->processed_by = Auth::id();
            $payout->save();

            $payout->load(['calculation', 'event', 'organizer', 'settlementPolicy']);

            return new PayoutResource($payout);
        });
    }

    public function failPayout(Request $request, Payout $payout)
    {
        $this->authorize('process', $payout);

        if (!$payout->isPending() && $payout->status !== Payout::STATUS_PROCESSING) {
            throw ValidationException::withMessages([
                'payout' => ['Only pending or processing payouts can be marked as failed.'],
            ]);
        }

        $payout->markAsFailed($request->notes);

        return new PayoutResource($payout->fresh());
    }

    public function settlementPolicies(Request $request)
    {
        $this->authorize('viewAny', SettlementPolicy::class);

        $policies = SettlementPolicy::orderBy('created_at', 'desc')->get();

        return SettlementPolicyResource::collection($policies);
    }

    public function storeSettlementPolicy(UpdateSettlementPolicyRequest $request)
    {
        $this->authorize('create', SettlementPolicy::class);

        $policy = SettlementPolicy::create($request->validated());

        return new SettlementPolicyResource($policy);
    }

    public function updateSettlementPolicy(UpdateSettlementPolicyRequest $request, SettlementPolicy $policy)
    {
        $this->authorize('update', $policy);

        $policy->update($request->validated());

        return new SettlementPolicyResource($policy);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAnyAdmin', Payout::class);

        $query = Payout::with(['calculation', 'event', 'organizer', 'settlementPolicy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payouts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'filename' => 'payouts_export_' . now()->format('Y_m_d_H_i_s') . '.csv',
            'count' => $payouts->count(),
            'data' => PayoutResource::collection($payouts),
        ]);
    }
}
