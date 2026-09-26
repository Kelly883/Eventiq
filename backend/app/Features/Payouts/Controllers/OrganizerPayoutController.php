<?php

namespace App\Features\Payouts\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Payouts\Models\Payout;
use App\Features\Payouts\Requests\ListPayoutsRequest;
use App\Features\Payouts\Resources\PayoutResource;
use App\Features\Payouts\Resources\PayoutCalculationResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrganizerPayoutController extends Controller
{
    public function list(ListPayoutsRequest $request)
    {
        $user = $request->user();

        // The ListPayoutsRequest FormRequest already guarantees the caller is an
        // organiser or admin (401 via bearer, 403 via authorize(), 400 via rules).
        $query = Payout::query();

        // Scoping: organisers only see their own payouts; admins see all.
        if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            $organizerId = $user->organizer ? $user->organizer->id : null;

            if (! $organizerId) {
                return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
            }

            $query->where('organizer_id', $organizerId);
        }

        // --- Filtering (every param is validated by ListPayoutsRequest) ---
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // IMPORTANT: use a plain range comparison (not whereDate) so the
        // predicate stays sargable and can leverage an index on created_at.
        // `whereDate('created_at', ...)` wraps the column in DATE(), which
        // defeats index usage and forces a full scan on large tables.
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date') . ' 00:00:00');
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }

        // --- Sorting (validated; column is whitelisted to avoid injection) ---
        $sortBy = $request->input('sort_by', 'createdAt');
        $sortDir = $request->input('sort_dir', 'desc');
        $columns = [
            'createdAt' => 'created_at',
            'payoutAmount' => 'payout_amount',
        ];
        $query->orderBy($columns[$sortBy], $sortDir);

        // --- Pagination (default 50, hard cap 100, validated by the request) ---
        $perPage = $request->filled('limit')
            ? (int) $request->input('limit')
            : (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);

        $payouts = $query->paginate($perPage, ['*'], 'page', $page);

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

                $payout->load(['organizer']);

        return new PayoutResource($payout);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Payout::query();
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $organizerId = $user->organizer?->id;
            if (!$organizerId) {
                return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
            }
            $query->where('organizer_id', $organizerId);
        }

        $totalPending = (clone $query)
            ->where('status', Payout::STATUS_PENDING)
            ->sum('payout_amount');

        $totalProcessing = (clone $query)
            ->where('status', Payout::STATUS_PROCESSING)
            ->sum('payout_amount');

        $totalProcessed = (clone $query)
            ->where('status', Payout::STATUS_COMPLETED)
            ->sum('payout_amount');

        $totalEarned = (clone $query)
            ->whereIn('status', [Payout::STATUS_COMPLETED, Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])
            ->sum('payout_amount');

        $nextPayout = (clone $query)
            ->where('status', Payout::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->first();

        return response()->json([
            'total_pending' => (float) $totalPending,
            'total_processing' => (float) $totalProcessing,
            'total_processed' => (float) $totalProcessed,
            'total_earned' => (float) $totalEarned,
            'next_payout' => $nextPayout ? (float) $nextPayout->payout_amount : 0,
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
