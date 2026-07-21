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
        $this->authorizeResource(PricingWindow::class, 'pricingWindow');
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
     */
    public function store(StorePricingWindowRequest $request, $eventId): JsonResponse
    {
        $data = $request->validated();
        $data['event_id'] = $eventId;

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

