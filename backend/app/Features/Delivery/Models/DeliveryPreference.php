<?php

namespace App\Features\Delivery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryPreference extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email_enabled',
        'sms_enabled',
        'dashboard_enabled',
        'push_enabled',
        'preferred_channel',
        'email_address',
        'phone_number',
        'quiet_hours_start',
        'quiet_hours_end',
        'max_daily_notifications',
        'language',
        'timezone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'quiet_hours_start' => 'string', // stored as time HH:MM:SS
        'quiet_hours_end' => 'string',   // stored as time HH:MM:SS
        'max_daily_notifications' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
