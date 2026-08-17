<?php

namespace App\Features\Delivery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryPreference extends Model
{
    use HasFactory, SoftDeletes;

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
        'event_cancellations',
        'refund_confirmations',
        'promotional_offers',
    ];

    protected $guarded = [
        'email_verified',
        'phone_verified',
    ];

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
        'event_cancellations',
        'refund_confirmations',
        'promotional_offers',
    ];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEnabledMethods(): array
    {
        $methods = [];
        if ($this->email_enabled) {
            $methods[] = 'email';
        }
        if ($this->sms_enabled) {
            $methods[] = 'sms';
        }
        if ($this->dashboard_enabled) {
            $methods[] = 'dashboard';
        }

        return $methods;
    }

    public function getPrimaryMethodStatus(): string
    {
        return match ($this->preferred_channel) {
            'email' => $this->email_verified ? 'verified' : 'unverified',
            'sms' => $this->phone_verified ? 'verified' : 'unverified',
            default => 'unverified',
        };
    }

    public function getBackupMethodStatus(): string
    {
        $primary = $this->preferred_channel;

        if ($primary === 'email') {
            return $this->phone_verified ? 'verified' : 'unverified';
        }

        if ($primary === 'sms') {
            return $this->email_verified ? 'verified' : 'unverified';
        }

        return 'unverified';
    }
}
