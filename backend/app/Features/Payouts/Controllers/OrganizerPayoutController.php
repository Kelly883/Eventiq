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
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // FIX: Use request-based auth check instead of $this->authorize()
        // BearerTokenAuth only sets request user resolver, not guard user
        $organizer = $user->organizer;
        if (!$organizer && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
        }

        // FIX: Use request user instead of Auth::user()
        $organizerId = $organizer?->id;

        // Admins can see all payouts; organizers only their own
        $query = Payout::query();
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            if (!$organizerId) {
                return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
            }
            $query->where('organizer_id', $organizerId);
        }

        $query->with(['calculation', 'event', 'settlementPolicy']);

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

    public function show(Request $request, Payout $payout)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admins can view any payout; organizers only their own
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $organizerId = $user->organizer?->id;
            if (!$organizerId || (string) $payout->organizer_id !== (string) $organizerId) {
                return response()->json(['message' => 'Forbidden — you do not own this payout'], 403);
            }
        }

        $payout->load(['calculation', 'event', 'settlementPolicy']);

        return new PayoutResource($payout);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admins see global summary; organizers see their own
        $query = Payout::query();
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $organizerId = $user->organizer?->id;
            if (!$organizerId) {
                return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
            }
            $query->where('organizer_id', $organizerId);
        }

        $organizerFilter = (!$user->hasRole('admin') && !$user->hasRole('super-admin'))
            ? $user->organizer?->id
            : null;

        $totalPending = (clone $query)
            ->when($organizerFilter, fn($q) => $q->where('organizer_id', $organizerFilter))
            ->where('status', Payout::STATUS_PENDING)
            ->sum('amount');

        $totalProcessing = (clone $query)
            ->when($organizerFilter, fn($q) => $q->where('organizer_id', $organizerFilter))
            ->where('status', Payout::STATUS_PROCESSING)
            ->sum('amount');

        $totalProcessed = (clone $query)
            ->when($organizerFilter, fn($q) => $q->where('organizer_id', $organizerFilter))
            ->where('status', Payout::STATUS_COMPLETED)
            ->sum('amount');

        $totalEarned = (clone $query)
            ->when($organizerFilter, fn($q) => $q->where('organizer_id', $organizerFilter))
            ->whereIn('status', [Payout::STATUS_COMPLETED, Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])
            ->sum('amount');

        $nextPayout = (clone $query)
            ->when($organizerFilter, fn($q) => $q->where('organizer_id', $organizerFilter))
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

    public function calculation(Request $request, Payout $payout)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admins can view any calculation; organizers only their own
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $organizerId = $user->organizer?->id;
            if (!$organizerId || (string) $payout->organizer_id !== (string) $organizerId) {
                return response()->json(['message' => 'Forbidden — you do not own this payout'], 403);
            }
        }

        if (!$payout->calculation) {
            throw ValidationException::withMessages([
                'payout' => ['No calculation found for this payout.'],
            ]);
        }

        return new PayoutCalculationResource($payout->calculation);
    }
}
