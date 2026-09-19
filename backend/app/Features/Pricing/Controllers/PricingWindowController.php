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
    private function authorizeEventOwner(Request $request, $event): void
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

        $ownsEvent = \App\Models\Event::where('id', $event)
            ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        abort_unless($ownsEvent, 403, 'You do not own this event.');
    }

    /**
     * Authorize that the authenticated user can access the event's pricing.
     * Admins/super-admins bypass ownership check. Organizers must own the event.
     */
    private function authorizeEventAccess(Request $request, $event): void
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

        $ownsEvent = \App\Models\Event::where('id', $event)
            ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        abort_unless($ownsEvent, 403, 'You do not own this event.');
    }

    /**
     * List pricing windows for an event.
     *
     * Query params:
     *   ?active_only=1       — only currently active windows
     *   ?ticket_category_id  — filter by ticket tier
     *   ?include_deleted=1   — include soft-deleted windows (organizers see their own, admins see all)
     *   ?per_page=50         — pagination limit
     */
    public function index(Request $request, $event): AnonymousResourceCollection
    {
        $this->authorizeEventAccess($request, $event);

        $includeDeleted = $request->boolean('include_deleted');

        // Only include soft-deleted windows if explicitly requested.
        // Organizers can see their own deleted windows; admins can see all.
        if ($includeDeleted) {
            $query = PricingWindow::forEvent($event)
                ->withTrashed()
                ->with(['event', 'ticketTier']);
        } else {
            $query = PricingWindow::forEvent($event)->with(['event', 'ticketTier']);
        }

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
    public function store(StorePricingWindowRequest $request, $event): JsonResponse
    {
        $this->authorizeEventOwner($request, $event);

        $data = $request->validated();
        $data['event_id'] = $event;
        $data['quantity_sold'] = 0; // Always start at 0, managed atomically via incrementSold()

        // Overlap detection: only check when the new window will be active.
        // This runs AFTER ownership verification so unauthorized users cannot
        // probe event pricing data via validation errors.
        $willBeActive = $request->has('is_active') ? $request->boolean('is_active') : true;
        if ($willBeActive) {
            $startDate = $data['start_date_time'];
            $endDate = $data['end_date_time'];
            $catId = $data['ticket_category_id'];

            $overlap = PricingWindow::where('event_id', $event)
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
                ], 409);
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
    public function show($event, $pricingWindow): PricingWindowResource
    {
        $window = PricingWindow::withTrashed()->findOrFail($pricingWindow);
        $user = request()->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $ownsEvent = \App\Models\Event::where('id', $event)
                ->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id))
                ->exists();
            abort_unless($ownsEvent, 403, 'You do not own this event.');
        }

        return new PricingWindowResource($window->load(['event', 'ticketTier']));
    }

    /**
     * Update a pricing window.
     */
    public function update(UpdatePricingWindowRequest $request, $event, $pricingWindow): JsonResponse
    {
        $window = PricingWindow::withTrashed()->findOrFail($pricingWindow);
        $this->authorizeEventOwner($request, $event);
        abort_unless((string) $window->event_id === (string) $event, 404);

        $validated = $request->validated();

        if (array_key_exists('quantity_limit', $validated) && $validated['quantity_limit'] < $window->quantity_sold) {
            return response()->json([
                'message' => "Cannot reduce quantity limit below {$window->quantity_sold} tickets already sold.",
                'errors' => ['quantity_limit' => ["Cannot reduce quantity limit below {$window->quantity_sold} tickets already sold."]],
            ], 422);
        }

        $hasDateChange = $request->has('start_date_time') || $request->has('end_date_time');
        if ($hasDateChange && $window->quantity_sold > 0) {
            return response()->json([
                'message' => 'Cannot change dates on a pricing window that has sold tickets.',
                'errors' => ['start_date_time' => ['Cannot change dates on a pricing window that has sold tickets.']],
            ], 422);
        }

        $hasDateOrCategoryChange = $request->has('start_date_time')
            || $request->has('end_date_time')
            || $request->has('ticket_category_id');

        $hasActivationChange = $request->has('is_active')
            && $request->boolean('is_active')
            && !$window->is_active;

        if ($hasDateOrCategoryChange || $hasActivationChange) {
            $startDate = $validated['start_date_time'] ?? $window->start_date_time;
            $endDate = $validated['end_date_time'] ?? $window->end_date_time;
            $catId = $validated['ticket_category_id'] ?? $window->ticket_category_id;

            $willBeActive = $request->has('is_active') ? $request->boolean('is_active') : $window->is_active;

            if ($willBeActive && $startDate && $endDate) {
                $overlap = PricingWindow::where('event_id', $event)
                    ->where('ticket_category_id', $catId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where('id', '!=', $window->id)
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
                    ], 409);
                }
            }
        }

        $oldValues = $window->toArray();
        $window->update($validated);

        return response()->json([
            'message' => 'Pricing window updated successfully.',
            'data' => new PricingWindowResource($window->fresh()->load(['event', 'ticketTier'])),
        ]);
    }

    /**
     * Soft-delete a pricing window.
     */
    public function destroy($event, $pricingWindow): JsonResponse
    {
        $window = PricingWindow::withTrashed()->findOrFail($pricingWindow);
        $this->authorizeEventOwner(request(), $event);
        abort_unless((string) $window->event_id === (string) $event, 404);

        if ($window->quantity_sold > 0) {
            return response()->json([
                'message' => 'Cannot delete a pricing window that has sold tickets. Restore it instead.',
                'errors' => ['window' => ['This window has sold tickets and cannot be deleted.']],
            ], 409);
        }

        $window->delete();

        return response()->json([
            'message' => 'Pricing window deleted successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted pricing window.
     */
    public function restore($event, $pricingWindow): JsonResponse
    {
        $window = PricingWindow::withTrashed()->findOrFail($pricingWindow);
        $this->authorizeEventOwner(request(), $event);
        abort_unless((string) $window->event_id === (string) $event, 404);

        // Overlap check on restore: only enforce for active windows.
        // An inactive window cannot create a functional overlap, so restoring
        // an inactive window should succeed even if its date range overlaps.
        if ($window->is_active) {
            $overlap = PricingWindow::where('event_id', $event)
                ->where('ticket_category_id', $window->ticket_category_id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $window->id)
                ->where(function ($q) use ($window) {
                    $q->whereBetween('start_date_time', [$window->start_date_time, $window->end_date_time])
                      ->orWhereBetween('end_date_time', [$window->start_date_time, $window->end_date_time])
                      ->orWhere(function ($q) use ($window) {
                          $q->where('start_date_time', '<=', $window->start_date_time)
                            ->where('end_date_time', '>=', $window->end_date_time);
                      });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'message' => 'An active pricing window already exists for this ticket category with overlapping dates.',
                    'errors' => ['start_date_time' => ['Cannot restore: overlaps with an active pricing window.']],
                ], 409);
            }
        }

        $window->restore();

        return response()->json([
            'message' => 'Pricing window restored successfully.',
            'data' => new PricingWindowResource($window->load(['event', 'ticketTier'])),
        ]);
    }

    /**
     * GET /api/organizer/events/{event}/pricing/preview — Preview pricing grouped by category.
     *
     * By default, soft-deleted windows are excluded. Pass ?include_deleted=1
     * to include them (admin/super-admin only).
     */
    public function preview(Request $request, $event): JsonResponse
    {
        $this->authorizeEventAccess($request, $event);

        $query = PricingWindow::forEvent($event)
            ->with(['ticketTier'])
            ->prioritized();

        // Exclude soft-deleted windows by default; allow admins to include them.
        $includeDeleted = $request->boolean('include_deleted');
        if (!$includeDeleted) {
            $query->whereNull('deleted_at');
        } elseif (!$request->user()->hasRole('admin') && !$request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Only admins can include deleted windows.'], 403);
        }

        $windows = $query->get();

        // Paginate grouped results for performance with large datasets
        $grouped = $windows->groupBy('ticket_category_id')->map(function ($group) {
            return [
                'ticket_category_id' => (string) $group->first()->ticket_category_id,
                'ticket_category_name' => optional($group->first()->ticketTier)->name,
                'windows' => PricingWindowResource::collection($group),
            ];
        })->values();

        return response()->json([
            'event_id' => (string) $event,
            'total_windows' => $windows->count(),
            'categories' => $grouped,
        ]);
    }
}

