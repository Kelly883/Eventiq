<?php

namespace App\Features\Payouts\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Payouts\Models\Payout;
use App\Features\Payouts\Resources\PayoutResource;
use App\Features\Payouts\Resources\PayoutCalculationResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrganizerPayoutController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payout::class);

        $query = Payout::where('organizer_id', Auth::user()->organizer_id)
            ->with(['calculation', 'event', 'settlementPolicy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        $payouts = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return PayoutResource::collection($payouts);
    }

    public function show(Payout $payout)
    {
        $this->authorize('view', $payout);

        $payout->load(['calculation', 'event', 'settlementPolicy']);

        return new PayoutResource($payout);
    }

    public function summary(Request $request)
    {
        $this->authorize('viewAny', Payout::class);

        $organizerId = Auth::user()->organizer_id;

        $totalPending = Payout::where('organizer_id', $organizerId)
            ->where('status', Payout::STATUS_PENDING)
            ->sum('amount');

        $totalProcessing = Payout::where('organizer_id', $organizerId)
            ->where('status', Payout::STATUS_PROCESSING)
            ->sum('amount');

        $totalProcessed = Payout::where('organizer_id', $organizerId)
            ->where('status', Payout::STATUS_COMPLETED)
            ->sum('amount');

        $totalEarned = Payout::where('organizer_id', $organizerId)
            ->whereIn('status', [Payout::STATUS_COMPLETED, Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])
            ->sum('amount');

        $nextPayout = Payout::where('organizer_id', $organizerId)
            ->where('status', Payout::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->first();

        return response()->json([
            'total_pending' => (float) $totalPending,
            'total_processing' => (float) $totalProcessing,
            'total_processed' => (float) $totalProcessed,
            'total_earned' => (float) $totalEarned,
            'next_payout' => $nextPayout ? (float) $nextPayout->amount : 0,
            'next_payout_date' => $nextPayout?->created_at,
        ]);
    }

    public function calculation(Payout $payout)
    {
        $this->authorize('view', $payout);

        if (!$payout->calculation) {
            throw ValidationException::withMessages([
                'payout' => ['No calculation found for this payout.'],
            ]);
        }

        return new PayoutCalculationResource($payout->calculation);
    }
}
