<?php

namespace App\Features\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];
}
