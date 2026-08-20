<?php

namespace App\Features\Dashboard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_dashboard_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'default_ticket_filter',
        'default_date_range',
        'show_recommendations',
        'show_activity_feed',
        'auto_refresh_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_recommendations' => 'boolean',
        'show_activity_feed' => 'boolean',
        'auto_refresh_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the dashboard preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find existing dashboard preferences for a user, or create them
     * with sensible defaults if none exist.
     *
     * This prevents null errors when a user visits their dashboard
     * for the first time and no preferences row has been created yet.
     *
     * Usage in controllers/services:
     *   $prefs = UserDashboardPreference::firstOrCreateForUser($user);
     *   // Now safe to use: $prefs->auto_refresh_enabled
     *
     * @param  \App\Models\User  $user
     * @return static
     */
    public static function firstOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'default_ticket_filter' => 'all',
                'default_date_range' => '30days',
                'show_recommendations' => true,
                'show_activity_feed' => true,
                'auto_refresh_enabled' => true,
            ]
        );
    }
}