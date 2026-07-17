<?php

namespace App\Features\Inventory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index($eventId)
    {
        // Get inventory for an event
    }

    public function adjust(Request $request, $eventId, $inventoryId)
    {
        // Adjust inventory
    }

    public function auditLogs($eventId)
    {
        // Get audit logs
    }

    public function lowStockAlerts($eventId)
    {
        // Get low stock alerts
    }

    public function export($eventId)
    {
        // Export inventory data
    }

    /**
     * GET /api/organizer/events/:eventId/inventory/summary
     */
    public function summary($eventId)
    {
        $inventories = \App\Features\Inventory\Models\TicketInventory::query()
            ->where('event_id', $eventId)
            ->orderBy('ticket_tier_id')
            ->get();

        $totalRemaining = 0;
        foreach ($inventories as $row) {
            $totalRemaining += (int) $row->remaining;
        }

        return response()->json([
            'data' => \App\Features\Inventory\Resources\TicketInventoryResource::collection($inventories),
            'event_id' => (int) $eventId,
            'summary' => [
                'total_rows' => $inventories->count(),
                'total_remaining' => $totalRemaining,
            ],
        ]);
    }
}
