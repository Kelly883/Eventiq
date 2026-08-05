<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Ticket;
use App\Models\Event;

class InventoryLockService
{
    /**
     * Lock inventory for an event during check-in operation.
     * Uses database-level locking with fallback to application-level locking.
     *
     * @param int $eventId
     * @param callable $callback
     * @return mixed
     */
    public static function withInventoryLock(int $eventId, callable $callback)
    {
        $lockKey = "inventory_lock_event_{$eventId}";
        
        // Acquire lock with 30 second timeout
        $acquired = Cache::lock($lockKey, 30)->block(5, function () use ($eventId, $callback) {
            // Use database transaction with row-level locking
            return DB::transaction(function () use ($eventId, $callback) {
                // Lock the inventory row for this event
                $inventory = DB::table('ticket_inventory')
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->first();
                
                if (!$inventory) {
                    throw new \Exception("Inventory not found for event {$eventId}");
                }
                
                // Execute the callback with locked inventory
                return $callback($inventory);
            });
        });
        
        if (!$acquired) {
            throw new \Exception("Unable to acquire inventory lock for event {$eventId}. Please try again.");
        }
        
        return $acquired;
    }
    
    /**
     * Check if check-in is allowed based on inventory limits.
     *
     * @param int $eventId
     * @return bool
     */
    public static function canCheckIn(int $eventId): bool
    {
        $inventory = DB::table('ticket_inventory')
            ->where('event_id', $eventId)
            ->first();
            
        if (!$inventory) {
            return false;
        }
        
        return $inventory->total_checked_in < $inventory->total_available;
    }
    
    /**
     * Get remaining capacity for an event.
     *
     * @param int $eventId
     * @return int
     */
    public static function getRemainingCapacity(int $eventId): int
    {
        $inventory = DB::table('ticket_inventory')
            ->where('event_id', $eventId)
            ->first();
            
        if (!$inventory) {
            return 0;
        }
        
        return max(0, $inventory->total_available - $inventory->total_checked_in);
    }
    
    /**
     * Atomically increment checked-in count.
     * Returns false if inventory is exhausted.
     *
     * @param int $eventId
     * @return bool
     */
    public static function incrementCheckedIn(int $eventId): bool
    {
        return static::withInventoryLock($eventId, function ($inventory) {
            if ($inventory->total_checked_in >= $inventory->total_available) {
                return false;
            }
            
            DB::table('ticket_inventory')
                ->where('event_id', $eventId)
                ->increment('total_checked_in');
            
            return true;
        });
    }
    
    /**
     * Atomically decrement checked-in count (for voided/refunded check-ins).
     *
     * @param int $eventId
     * @return bool
     */
    public static function decrementCheckedIn(int $eventId): bool
    {
        return static::withInventoryLock($eventId, function ($inventory) {
            if ($inventory->total_checked_in <= 0) {
                return false;
            }
            
            DB::table('ticket_inventory')
                ->where('event_id', $eventId)
                ->decrement('total_checked_in');
            
            return true;
        });
    }
    
    /**
     * Atomically increment void count.
     *
     * @param int $eventId
     * @return bool
     */
    public static function incrementVoid(int $eventId): bool
    {
        return static::withInventoryLock($eventId, function ($inventory) {
            if ($inventory->total_void >= $inventory->total_available) {
                return false;
            }
            
            DB::table('ticket_inventory')
                ->where('event_id', $eventId)
                ->increment('total_void');
            
            return true;
        });
    }
    
    /**
     * Sync inventory counts with actual ticket data.
     * Should be run periodically as a sanity check.
     *
     * @param int $eventId
     * @return array
     */
    public static function syncInventory(int $eventId): array
    {
        $stats = DB::table('tickets')
            ->where('event_id', $eventId)
            ->select(
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(CASE WHEN status = "checked_in" THEN 1 ELSE 0 END) as actual_checked_in'),
                DB::raw('SUM(CASE WHEN status = "void" THEN 1 ELSE 0 END) as actual_void')
            )
            ->first();
        
        $updated = DB::table('ticket_inventory')
            ->where('event_id', $eventId)
            ->update([
                'total_checked_in' => $stats->actual_checked_in ?? 0,
                'total_void' => $stats->actual_void ?? 0,
                'last_updated_at' => now(),
            ]);
        
        return [
            'synced' => (bool) $updated,
            'total_tickets' => $stats->total_tickets ?? 0,
            'actual_checked_in' => $stats->actual_checked_in ?? 0,
            'actual_void' => $stats->actual_void ?? 0,
        ];
    }
}

</parameter>
<command>type nul > backend/app/Services/InventoryLockService.php</command>
<requires_approval>false</requires_approval>
</execute_command>