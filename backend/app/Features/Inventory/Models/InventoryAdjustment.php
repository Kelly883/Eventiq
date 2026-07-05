<?php

namespace App\Features\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_inventory_id',
        'user_id',
        'adjustment_type',
        'quantity_change',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
    ];

    public function ticketInventory(): BelongsTo
    {
        return $this->belongsTo(TicketInventory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
