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
        // store/update/destroy are authorized per-method below because they
        // need the URL event scope, which authorizeResource cannot provide.
        $this->authorizeResource(PricingWindow::class, 'pricingWindow', [
            'except' => ['create', 'store', 'update', 'destroy'],
        ]);
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

        abort_unless($user->hasRole('organizer'), 403, 'Only organizers can manage pricing windows.');

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
        return new PricingWindowResource($pricingWindow->load(['event', 'ticketTier']));
    }

    /**
     * Update a pricing window.
     */
    public function update(UpdatePricingWindowRequest $request, $eventId, PricingWindow $pricingWindow): JsonResponse
    {
        $this->authorizeEventOwner($request, $eventId);
        abort_unless((string) $pricingWindow->event_id === (string) $eventId, 404);

        $pricingWindow->update($request->validated());

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
}

