<?php

namespace App\Features\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TierPerformance extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'ticket_tier_id', 'tickets_sold', 'revenue'];
}
