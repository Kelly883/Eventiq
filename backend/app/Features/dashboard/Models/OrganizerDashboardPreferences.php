<?php

namespace App\Features\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function expandedEvent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class, 'expanded_event_id');
    }

    public function getDefaultEventFilterAttribute($value): string
    {
        return $value ?? 'all';
    }

    public function getDefaultDateRangeAttribute($value): string
    {
        return $value ?? 'last_30_days';
    }

    public function getShowActivityFeedAttribute($value): bool
    {
        return $value ?? true;
    }

    public function getAutoRefreshEnabledAttribute($value): bool
    {
        return $value ?? false;
    }
}
