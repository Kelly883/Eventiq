<?php

namespace App\Features\Dashboard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserDashboardPreference extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_dashboard_preferences';

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

        $id = (string) \Illuminate\Support\Str::uuid();
        DB::table('user_dashboard_preferences')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'default_ticket_filter' => 'all',
            'default_date_range' => '30days',
            'show_recommendations' => 1,
            'show_activity_feed' => 1,
            'auto_refresh_enabled' => 1,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return static::find($id);
    }

    /**
     * Update preferences using DB::table to avoid SQLite boolean issues.
     */
    public static function updatePreferences(User $user, array $data): self
    {
        $prefs = static::where('user_id', $user->id)->first();

        if (!$prefs) {
            return static::firstOrCreateForUser($user);
        }

        $updateData = [];
        $columns = ['default_ticket_filter', 'default_date_range', 'show_recommendations', 'show_activity_feed', 'auto_refresh_enabled'];
        
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $updateData[$col] = in_array($col, ['show_recommendations', 'show_activity_feed', 'auto_refresh_enabled']) 
                    ? (int) $data[$col] 
                    : $data[$col];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = now()->toDateTimeString();
            DB::table('user_dashboard_preferences')
                ->where('user_id', $user->id)
                ->update($updateData);
        }

        return static::where('user_id', $user->id)->first();
    }
}
