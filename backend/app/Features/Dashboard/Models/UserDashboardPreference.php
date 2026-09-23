<?php

namespace App\Features\Dashboard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_dashboard_preferences';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'default_ticket_filter',
        'default_date_range',
        'show_recommendations',
        'show_activity_feed',
        'auto_refresh_enabled',
    ];

    protected $casts = [
        'show_recommendations' => 'boolean',
        'show_activity_feed' => 'boolean',
        'auto_refresh_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find existing dashboard preferences for a user, or create them
     * with sensible defaults if none exist.
     *
     * Uses DB::table to avoid SQLite datatype mismatch on boolean columns.
     */
    public static function firstOrCreateForUser(User $user): self
    {
        $existing = static::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_id' => $user->id,
            'default_ticket_filter' => 'all',
            'default_date_range' => '30days',
            'show_recommendations' => true,
            'show_activity_feed' => true,
            'auto_refresh_enabled' => true,
        ]);
    }

    /**
     * Update preferences using DB::table to avoid SQLite boolean issues.
     */
    public static function updatePreferences(User $user, array $data): self
    {
        $prefs = static::firstOrCreateForUser($user);

        $columns = ['default_ticket_filter', 'default_date_range', 'show_recommendations', 'show_activity_feed', 'auto_refresh_enabled'];
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $prefs->{$col} = $data[$col];
            }
        }

        $prefs->save();

        return $prefs;
    }
}
