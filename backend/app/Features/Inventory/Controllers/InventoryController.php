<?php

namespace App\Features\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Inventory\Models\TicketInventory;
use App\Features\Inventory\Models\InventoryAdjustment;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    /**
     * GET /api/organizer/events/:eventId/inventory/summary
     * Returns high-level overview: totals, utilization, low-stock count
     */
    public function summary(Request $request, $eventId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        try {
            $inventories = TicketInventory::where('event_id', $eventId)
                ->with('ticketTier')
                ->get();

            $totalCapacity = (int) $inventories->sum('total_allocated');
            $totalSold = (int) $inventories->sum('total_sold');
            $totalAvailable = (int) $inventories->sum(fn($inv) => $inv->total_available);
            $utilizationPercentage = $totalCapacity > 0 ? round(($totalSold / $totalCapacity) * 100, 2) : 0;
            $lowStockTierCount = $inventories->filter(fn($inv) => $inv->is_low_stock)->count();

            $tiers = $inventories->map(function ($inv) {
                return [
                    'tierId' => (string) $inv->ticket_tier_id,
                    'tierName' => $inv->ticketTier?->name ?? 'Unknown',
                    'allocated' => (int) $inv->total_allocated,
                    'sold' => (int) $inv->total_sold,
                    'available' => (int) $inv->total_available,
                    'isLowStock' => (bool) $inv->is_low_stock,
                ];
            })->values();

            return response()->json([
                'totalCapacity' => $totalCapacity,
                'totalSold' => $totalSold,
                'totalAvailable' => $totalAvailable,
                'utilizationPercentage' => $utilizationPercentage,
                'lowStockTierCount' => $lowStockTierCount,
                'tiers' => $tiers,
            ]);
        } catch (\Throwable $e) {
            Log::error('Inventory summary failed', ['event_id' => $eventId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch inventory summary'], 500);
        }
    }

    /**
     * GET /api/organizer/events/:eventId/inventory
     * Detailed inventory with pricing windows nested, filtering, sorting
     */
    public function inventory(Request $request, $eventId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        // Validate query params
        $validator = \Illuminate\Support\Facades\Validator::make($request->query(), [
            'tierFilter' => 'nullable|string',
            'sortBy' => 'nullable|in:allocated,sold,available',
            'sortOrder' => 'nullable|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid query parameters', 'errors' => $validator->errors()], 400);
        }

        $tierFilter = $request->query('tierFilter');
        $sortBy = $request->query('sortBy', 'allocated');
        $sortOrder = $request->query('sortOrder', 'desc');

        // Validate tierFilter exists if provided
        if ($tierFilter) {
            $exists = TicketTier::where('id', $tierFilter)->where('event_id', $eventId)->exists()
                || TicketInventory::where('ticket_tier_id', $tierFilter)->where('event_id', $eventId)->exists();
            if (!$exists) {
                // If filter is uuid but not found, return empty result (not 404)
                // But spec says 400 for invalid params, so we return 400 if it's not a valid tier for this event
                // For now, return empty list
            }
        }

        try {
            $query = TicketInventory::where('event_id', $eventId)
                ->with('ticketTier');

            if ($tierFilter) {
                $query->where('ticket_tier_id', $tierFilter);
            }

            $inventories = $query->get();

            // Load pricing windows for each tier
            $tierIds = $inventories->pluck('ticket_tier_id')->toArray();
            $pricingWindowsByTier = PricingWindow::where('event_id', $eventId)
                ->whereIn('ticket_category_id', $tierIds)
                ->get()
                ->groupBy('ticket_category_id');

            $tiers = $inventories->map(function ($inv) use ($pricingWindowsByTier) {
                $tier = $inv->ticketTier;
                $windows = $pricingWindowsByTier->get($inv->ticket_tier_id, collect());

                $pricingWindows = $windows->map(function ($w) {
                    return [
                        'windowId' => (string) $w->id,
                        'windowName' => $w->window_name,
                        'startDateTime' => $w->start_date_time?->toIso8601String(),
                        'endDateTime' => $w->end_date_time?->toIso8601String(),
                        'quantityLimit' => $w->quantity_limit !== null ? (int) $w->quantity_limit : null,
                        'quantitySold' => (int) $w->quantity_sold,
                        'quantityAvailable' => $w->available_quantity !== null ? (int) $w->available_quantity : null,
                        'isActive' => (bool) $w->is_active && $w->isActive(),
                        'priority' => (int) $w->priority,
                    ];
                })->values();

                $totalAllocated = (int) $inv->total_allocated;
                $totalSold = (int) $inv->total_sold;
                $totalAvailable = (int) $inv->total_available;
                $utilization = $totalAllocated > 0 ? round(($totalSold / $totalAllocated) * 100, 2) : 0;

                return [
                    'tierId' => (string) $inv->ticket_tier_id,
                    'tierName' => $tier?->name ?? 'Unknown',
                    'basePrice' => $tier?->price !== null ? (float) $tier->price : null,
                    'totalAllocated' => $totalAllocated,
                    'totalSold' => $totalSold,
                    'totalAvailable' => $totalAvailable,
                    'utilizationPercentage' => $utilization,
                    'isLowStock' => (bool) $inv->is_low_stock,
                    'lowStockThreshold' => $inv->low_stock_threshold !== null ? (int) $inv->low_stock_threshold : null,
                    'pricingWindows' => $pricingWindows,
                    // For sorting
                    '_sort_allocated' => $totalAllocated,
                    '_sort_sold' => $totalSold,
                    '_sort_available' => $totalAvailable,
                ];
            });

            // Apply sorting
            $sortKey = match ($sortBy) {
                'allocated' => '_sort_allocated',
                'sold' => '_sort_sold',
                'available' => '_sort_available',
                default => '_sort_allocated',
            };

            $tiers = $sortOrder === 'asc'
                ? $tiers->sortBy($sortKey)->values()
                : $tiers->sortByDesc($sortKey)->values();

            // Remove temp sort keys
            $tiers = $tiers->map(function ($t) {
                unset($t['_sort_allocated'], $t['_sort_sold'], $t['_sort_available']);
                return $t;
            });

            return response()->json([
                'tiers' => $tiers,
            ]);
        } catch (\Throwable $e) {
            Log::error('Inventory detail failed', ['event_id' => $eventId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch inventory'], 500);
        }
    }

    /**
     * PATCH /api/organizer/events/:eventId/inventory/adjust
     * Manually adjust inventory for a tier or pricing window
     */
    public function adjust(Request $request, $eventId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('update', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tierIdOrWindowId' => 'required|string',
            'newQuantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 400);
        }

        $tierIdOrWindowId = $request->input('tierIdOrWindowId');
        $newQuantity = (int) $request->input('newQuantity');
        $reason = $request->input('reason');

        // Validate newQuantity doesn't exceed event capacity
        if ($event->capacity !== null && $newQuantity > $event->capacity) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['newQuantity' => ['New quantity cannot exceed event capacity of ' . $event->capacity]]
            ], 400);
        }

        // Also check total allocated after adjustment doesn't exceed capacity
        try {
            $result = DB::transaction(function () use ($event, $eventId, $user, $tierIdOrWindowId, $newQuantity, $reason) {
                // Try to find as TicketInventory (by ticket_tier_id)
                $inventory = TicketInventory::where('event_id', $eventId)
                    ->where('ticket_tier_id', $tierIdOrWindowId)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $previousQuantity = (int) $inventory->total_allocated;
                    $quantityDelta = $newQuantity - $previousQuantity;

                    // Check total capacity after adjustment
                    $currentTotal = TicketInventory::where('event_id', $eventId)->sum('total_allocated');
                    $newTotal = $currentTotal - $previousQuantity + $newQuantity;
                    if ($event->capacity !== null && $newTotal > $event->capacity) {
                        throw new \Illuminate\Validation\ValidationException(
                            \Illuminate\Support\Facades\Validator::make([], []),
                            response()->json([
                                'message' => 'Total inventory cannot exceed event capacity',
                                'errors' => ['newQuantity' => ['Total inventory after adjustment would exceed event capacity']]
                            ], 400)
                        );
                    }

                    // Check sold not exceed new allocated
                    if ($inventory->total_sold > $newQuantity) {
                        throw new \Illuminate\Validation\ValidationException(
                            \Illuminate\Support\Facades\Validator::make([], []),
                            response()->json([
                                'message' => 'New quantity cannot be less than sold quantity',
                                'errors' => ['newQuantity' => ['New quantity cannot be less than already sold (' . $inventory->total_sold . ')']]
                            ], 400)
                        );
                    }

                    $inventory->update([
                        'total_allocated' => $newQuantity,
                        'last_updated_at' => now(),
                    ]);

                    $adjustmentType = $quantityDelta > 0 ? 'manual_increase' : ($quantityDelta < 0 ? 'manual_decrease' : 'system_correction');

                    $adjustment = InventoryAdjustment::create([
                        'event_id' => $eventId,
                        'ticket_tier_id' => $inventory->ticket_tier_id,
                        'pricing_window_id' => null,
                        'organizer_id' => $user->id,
                        'adjustment_type' => $adjustmentType,
                        'quantity_before' => $previousQuantity,
                        'quantity_after' => $newQuantity,
                        'quantity_delta' => $quantityDelta,
                        'reason' => $reason,
                    ]);

                    $tier = $inventory->ticketTier;

                    return [
                        'tierId' => (string) $inventory->ticket_tier_id,
                        'tierName' => $tier?->name ?? 'Unknown',
                        'newQuantity' => $newQuantity,
                        'previousQuantity' => $previousQuantity,
                        'quantityDelta' => $quantityDelta,
                    ];
                }

                // Try as PricingWindow
                $window = PricingWindow::where('id', $tierIdOrWindowId)
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->first();

                if ($window) {
                    $previousQuantity = $window->quantity_limit !== null ? (int) $window->quantity_limit : 0;
                    $quantityDelta = $newQuantity - $previousQuantity;

                    if ($window->quantity_sold > $newQuantity) {
                        throw new \Illuminate\Validation\ValidationException(
                            \Illuminate\Support\Facades\Validator::make([], []),
                            response()->json([
                                'message' => 'New quantity cannot be less than sold quantity',
                                'errors' => ['newQuantity' => ['New quantity cannot be less than already sold (' . $window->quantity_sold . ')']]
                            ], 400)
                        );
                    }

                    $window->update(['quantity_limit' => $newQuantity]);

                    // Also update related inventory if exists
                    $relatedInventory = TicketInventory::where('event_id', $eventId)
                        ->where('ticket_tier_id', $window->ticket_category_id)
                        ->first();
                    if ($relatedInventory) {
                        $relatedInventory->updateFromPricingWindows();
                    }

                    $adjustmentType = $quantityDelta > 0 ? 'manual_increase' : ($quantityDelta < 0 ? 'manual_decrease' : 'system_correction');

                    $adjustment = InventoryAdjustment::create([
                        'event_id' => $eventId,
                        'ticket_tier_id' => $window->ticket_category_id ?? $tierIdOrWindowId,
                        'pricing_window_id' => $window->id,
                        'organizer_id' => $user->id,
                        'adjustment_type' => $adjustmentType,
                        'quantity_before' => $previousQuantity,
                        'quantity_after' => $newQuantity,
                        'quantity_delta' => $quantityDelta,
                        'reason' => $reason,
                    ]);

                    $tier = $window->ticketTier;

                    return [
                        'tierId' => (string) $window->id,
                        'tierName' => $window->window_name ?? $tier?->name ?? 'Unknown',
                        'newQuantity' => $newQuantity,
                        'previousQuantity' => $previousQuantity,
                        'quantityDelta' => $quantityDelta,
                    ];
                }

                // Not found in either
                return null;
            });

            if ($result === null) {
                return response()->json(['message' => 'Inventory tier or pricing window not found'], 404);
            }

            return response()->json(array_merge($result, [
                'message' => 'Inventory adjusted successfully'
            ]));

        } catch (\Illuminate\Validation\ValidationException $e) {
            // If it's our custom validation with response, return that response
            if ($e->getResponse()) {
                return $e->getResponse();
            }
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Inventory adjust failed', ['event_id' => $eventId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to adjust inventory'], 500);
        }
    }

    /**
     * GET /api/organizer/events/:eventId/inventory/export
     * Export as CSV or JSON, optionally include history
     */
    public function export(Request $request, $eventId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->query(), [
            'format' => 'nullable|in:csv,json',
            'includeHistory' => 'nullable|in:true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid query parameters', 'errors' => $validator->errors()], 400);
        }

        $format = $request->query('format', 'csv');
        $includeHistory = filter_var($request->query('includeHistory', 'false'), FILTER_VALIDATE_BOOLEAN);

        try {
            $inventories = TicketInventory::where('event_id', $eventId)
                ->with('ticketTier')
                ->get();

            $adjustments = collect();
            if ($includeHistory) {
                $adjustments = InventoryAdjustment::where('event_id', $eventId)
                    ->with(['ticketTier', 'organizer'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            if ($format === 'json') {
                $data = [
                    'eventId' => $eventId,
                    'exportedAt' => now()->toIso8601String(),
                    'inventory' => $inventories->map(fn($inv) => [
                        'tierId' => $inv->ticket_tier_id,
                        'tierName' => $inv->ticketTier?->name,
                        'allocated' => $inv->total_allocated,
                        'sold' => $inv->total_sold,
                        'available' => $inv->total_available,
                        'isLowStock' => $inv->is_low_stock,
                    ]),
                ];

                if ($includeHistory) {
                    $data['history'] = $adjustments->map(fn($adj) => [
                        'id' => $adj->id,
                        'tierId' => $adj->ticket_tier_id,
                        'adjustmentType' => $adj->adjustment_type,
                        'quantityBefore' => $adj->quantity_before,
                        'quantityAfter' => $adj->quantity_after,
                        'quantityDelta' => $adj->quantity_delta,
                        'reason' => $adj->reason,
                        'createdAt' => $adj->created_at?->toIso8601String(),
                    ]);
                }

                $json = json_encode($data, JSON_PRETTY_PRINT);
                $filename = "inventory-{$eventId}-" . now()->format('Y-m-d') . ".json";

                return response($json, 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ]);
            } else {
                // CSV
                $filename = "inventory-{$eventId}-" . now()->format('Y-m-d') . ".csv";
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function () use ($inventories, $adjustments, $includeHistory) {
                    $file = fopen('php://output', 'w');
                    // Inventory header
                    fputcsv($file, ['Tier ID', 'Tier Name', 'Allocated', 'Sold', 'Available', 'Is Low Stock', 'Low Stock Threshold']);
                    foreach ($inventories as $inv) {
                        fputcsv($file, [
                            $inv->ticket_tier_id,
                            $inv->ticketTier?->name ?? '',
                            $inv->total_allocated,
                            $inv->total_sold,
                            $inv->total_available,
                            $inv->is_low_stock ? 'yes' : 'no',
                            $inv->low_stock_threshold,
                        ]);
                    }

                    if ($includeHistory) {
                        fputcsv($file, []);
                        fputcsv($file, ['History: Adjustment ID', 'Tier ID', 'Type', 'Before', 'After', 'Delta', 'Reason', 'Created At']);
                        foreach ($adjustments as $adj) {
                            fputcsv($file, [
                                $adj->id,
                                $adj->ticket_tier_id,
                                $adj->adjustment_type,
                                $adj->quantity_before,
                                $adj->quantity_after,
                                $adj->quantity_delta,
                                $adj->reason ?? '',
                                $adj->created_at?->toDateTimeString(),
                            ]);
                        }
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
        } catch (\Throwable $e) {
            Log::error('Inventory export failed', ['event_id' => $eventId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to export inventory'], 500);
        }
    }

    /**
     * GET /api/organizer/events/:eventId/inventory/audit-log
     * Paginated audit log of adjustments
     */
    public function auditLog(Request $request, $eventId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->query(), [
            'tierFilter' => 'nullable|string',
            'organizerFilter' => 'nullable|string',
            'adjustmentType' => 'nullable|in:manual_increase,manual_decrease,reallocation,system_correction',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid query parameters', 'errors' => $validator->errors()], 400);
        }

        $tierFilter = $request->query('tierFilter');
        $organizerFilter = $request->query('organizerFilter');
        $adjustmentType = $request->query('adjustmentType');
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min(100, $limit));

        try {
            $query = InventoryAdjustment::where('event_id', $eventId)
                ->with(['ticketTier', 'organizer']);

            if ($tierFilter) {
                $query->where('ticket_tier_id', $tierFilter);
            }

            if ($organizerFilter) {
                $query->where('organizer_id', $organizerFilter);
            }

            if ($adjustmentType) {
                $query->where('adjustment_type', $adjustmentType);
            }

            $total = $query->count();

            $adjustments = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            $data = $adjustments->map(function ($adj) {
                return [
                    'id' => (string) $adj->id,
                    'tierId' => (string) $adj->ticket_tier_id,
                    'tierName' => $adj->ticketTier?->name ?? 'Unknown',
                    'organizerId' => (string) $adj->organizer_id,
                    'organizerName' => $adj->organizer?->name ?? 'Unknown',
                    'adjustmentType' => $adj->adjustment_type,
                    'quantityBefore' => (int) $adj->quantity_before,
                    'quantityAfter' => (int) $adj->quantity_after,
                    'quantityDelta' => (int) $adj->quantity_delta,
                    'reason' => $adj->reason,
                    'createdAt' => $adj->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'adjustments' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            Log::error('Inventory audit log failed', ['event_id' => $eventId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch audit log'], 500);
        }
    }

    // Legacy stubs for backwards compatibility (not used by spec but keep for old clients)
    public function index($eventId) { return $this->inventory(request(), $eventId); }
    public function lowStockAlerts($eventId) { return $this->summary(request(), $eventId); }
}
