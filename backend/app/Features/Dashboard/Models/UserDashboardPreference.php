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

    public static function firstOrCreateForUser(User $user): self
    {
        $existing = static::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $id = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('user_dashboard_preferences')->insert([
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

        return static::where('id', $id)->first();
    }

    public static function updatePreferences(User $user, array $data): self
    {
        $prefs = static::firstOrCreateForUser($user);

        $columns = ['default_ticket_filter', 'default_date_range', 'show_recommendations', 'show_activity_feed', 'auto_refresh_enabled'];
        $updateData = [];
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $updateData[$col] = $data[$col];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = now()->toDateTimeString();
            \Illuminate\Support\Facades\DB::table('user_dashboard_preferences')
                ->where('user_id', $user->id)
                ->update($updateData);
        }

        return static::where('user_id', $user->id)->first();
    }
}
