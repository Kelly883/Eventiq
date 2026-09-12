<?php

namespace App\Features\Pricing\Controllers;

use App\Features\Pricing\Models\PricingWindow;
use App\Features\Pricing\Requests\StorePricingWindowRequest;
use App\Features\Pricing\Requests\UpdatePricingWindowRequest;
use App\Features\Pricing\Resources\PricingWindowResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PricingWindowController extends Controller
{
    public function __construct()
    {
        // Authorization is handled per-method below because this controller
        // does not extend Illuminate\Routing\Controller and therefore does
        // not have the middleware() method required by authorizeResource().
    }

    /**
     * Only the owning organizer (or an admin) may mutate an event's windows.
     */
    private function authorizeEventOwner(Request $request, $eventId): void
    {
        $user = $request->user();

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return;
        }

        // Align with Organizer\EventController: accept either the role column
        // or an attached organizer profile, because some seed data populates
        // the legacy `role` string column without filling `role_id`.
        $isOrganizer = $user->hasRole('organizer')
            || (string) $user->getAttribute('role') === 'organizer'
            || (bool) $user->organizer?->id;

        abort_unless($isOrganizer, 403, 'Only organizers can manage pricing windows.');

        $ownsEvent = \App\Models\Event::where('id', $eventId)
            ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        abort_unless($ownsEvent, 403, 'You do not own this event.');
    }

    /**
     * Authorize that the authenticated user can access the event's pricing.
     * Admins/super-admins bypass ownership check. Organizers must own the event.
     */
    private function authorizeEventAccess(Request $request, $eventId): void
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return;
        }

        $isOrganizer = $user->hasRole('organizer')
            || (string) $user->getAttribute('role') === 'organizer'
            || (bool) $user->organizer?->id;

        abort_unless($isOrganizer, 403, 'Only organizers can manage pricing windows.');

        $ownsEvent = \App\Models\Event::where('id', $eventId)
            ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        abort_unless($ownsEvent, 403, 'You do not own this event.');
    }

    /**
     * List pricing windows for an event.
     */
    public function index(Request $request, $eventId): AnonymousResourceCollection
    {
        $this->authorizeEventAccess($request, $eventId);

        $query = PricingWindow::forEvent($eventId)->with(['event', 'ticketTier']);

        // Optional filters
        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->has('ticket_category_id')) {
            $query->forTicketTier($request->input('ticket_category_id'));
        }

        $windows = $query->prioritized()->paginate($request->input('per_page', 50));

        return PricingWindowResource::collection($windows);
    }

    /**
     * Create a new pricing window.
     * Note: quantity_sold is forced to 0 on creation — it is only incremented
     * via incrementSold() during checkout to maintain atomicity.
     */
    public function store(StorePricingWindowRequest $request, $eventId): JsonResponse
    {
        $this->authorizeEventOwner($request, $eventId);

        $data = $request->validated();
        $data['event_id'] = $eventId;
        $data['quantity_sold'] = 0; // Always start at 0, managed atomically via incrementSold()

        // Overlap detection: only check when the new window will be active.
        // This runs AFTER ownership verification so unauthorized users cannot
        // probe event pricing data via validation errors.
        $willBeActive = $request->has('is_active') ? $request->boolean('is_active') : true;
        if ($willBeActive) {
            $startDate = $data['start_date_time'];
            $endDate = $data['end_date_time'];
            $catId = $data['ticket_category_id'];

            $overlap = PricingWindow::where('event_id', $eventId)
                ->where('ticket_category_id', $catId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date_time', [$startDate, $endDate])
                      ->orWhereBetween('end_date_time', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date_time', '<=', $startDate)
                            ->where('end_date_time', '>=', $endDate);
                      });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'message' => 'An active pricing window already exists for this ticket category with overlapping dates.',
                    'errors' => ['start_date_time' => ['An active pricing window already exists for this ticket category with overlapping dates.']],
                ], 422);
            }
        }

        $window = PricingWindow::create($data);

        return response()->json([
            'message' => 'Pricing window created successfully.',
            'data' => new PricingWindowResource($window->load(['event', 'ticketTier'])),
        ], 201);
    }

    /**
     * Show a single pricing window.
     */
    public function show($eventId, PricingWindow $pricingWindow): PricingWindowResource
    {
        $user = request()->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $ownsEvent = \App\Models\Event::where('id', $eventId)
                ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
                ->exists();
            abort_unless($ownsEvent, 403, 'You do not own this event.');
        }

        return new PricingWindowResource($pricingWindow->load(['event', 'ticketTier']));
    }

    /**
     * Update a pricing window.
     */
    public function update(UpdatePricingWindowRequest $request, $eventId, PricingWindow $pricingWindow): JsonResponse
    {
        $this->authorizeEventOwner($request, $eventId);
        abort_unless((string) $pricingWindow->event_id === (string) $eventId, 404);

        $validated = $request->validated();

        // Overlap detection on update: only when dates or category are changing
        // and the window is currently active or will become active.
        $hasDateOrCategoryChange = $request->has('start_date_time')
            || $request->has('end_date_time')
            || $request->has('ticket_category_id');

        if ($hasDateOrCategoryChange) {
            $startDate = $validated['start_date_time'] ?? $pricingWindow->start_date_time;
            $endDate = $validated['end_date_time'] ?? $pricingWindow->end_date_time;
            $catId = $validated['ticket_category_id'] ?? $pricingWindow->ticket_category_id;
            $willBeActive = $request->has('is_active') ? $request->boolean('is_active') : $pricingWindow->is_active;

            if ($willBeActive && $startDate && $endDate) {
                $overlap = PricingWindow::where('event_id', $eventId)
                    ->where('ticket_category_id', $catId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where('id', '!=', $pricingWindow->id)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date_time', [$startDate, $endDate])
                          ->orWhereBetween('end_date_time', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date_time', '<=', $startDate)
                                ->where('end_date_time', '>=', $endDate);
                          });
                    })
                    ->exists();

                if ($overlap) {
                    return response()->json([
                        'message' => 'An active pricing window already exists for this ticket category with overlapping dates.',
                        'errors' => ['start_date_time' => ['An active pricing window already exists for this ticket category with overlapping dates.']],
                    ], 422);
                }
            }
        }

        $pricingWindow->update($validated);

        return response()->json([
            'message' => 'Pricing window updated successfully.',
            'data' => new PricingWindowResource($pricingWindow->fresh()->load(['event', 'ticketTier'])),
        ]);
    }

    /**
     * Soft-delete a pricing window.
     */
    public function destroy($eventId, PricingWindow $pricingWindow): JsonResponse
    {
        $this->authorizeEventOwner(request(), $eventId);
        abort_unless((string) $pricingWindow->event_id === (string) $eventId, 404);

        $pricingWindow->delete();

        return response()->json([
            'message' => 'Pricing window deleted successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted pricing window.
     */
    public function restore($eventId, $id): JsonResponse
    {
        $window = PricingWindow::withTrashed()->findOrFail($id);
        $this->authorize('restore', $window);

        $window->restore();

        return response()->json([
            'message' => 'Pricing window restored successfully.',
            'data' => new PricingWindowResource($window->load(['event', 'ticketTier'])),
        ]);
    }

    /**
     * GET /api/organizer/events/{event}/pricing/preview — Preview pricing grouped by category.
     */
    public function preview(Request $request, $eventId): JsonResponse
    {
        $this->authorizeEventAccess($request, $eventId);

        $windows = PricingWindow::forEvent($eventId)
            ->with(['ticketTier'])
            ->prioritized()
            ->get();

        $grouped = $windows->groupBy('ticket_category_id')->map(function ($group) {
            return [
                'ticket_category_id' => (string) $group->first()->ticket_category_id,
                'ticket_category_name' => optional($group->first()->ticketTier)->name,
                'windows' => PricingWindowResource::collection($group),
            ];
        })->values();

        return response()->json([
            'event_id' => (string) $eventId,
            'total_windows' => $windows->count(),
            'categories' => $grouped,
        ]);
    }
}

