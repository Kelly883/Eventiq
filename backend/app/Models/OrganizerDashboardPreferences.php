<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizerDashboardPreferences extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'default_event_filter',
        'default_date_range',
        'expanded_event_id',
        'show_activity_feed',
        'auto_refresh_enabled',
    ];

    protected $casts = [
        'show_activity_feed' => 'boolean',
        'auto_refresh_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function expandedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'expanded_event_id');
    }
}
