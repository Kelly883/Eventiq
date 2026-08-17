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
        'email_verified',
        'phone_verified',
    ];

    protected $guarded = [];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'quiet_hours_start' => 'string',
        'quiet_hours_end' => 'string',
        'max_daily_notifications' => 'integer',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'deleted_at' => 'datetime',
        'email_address' => 'encrypted',
        'phone_number' => 'encrypted',
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
            'email' => ($this->email_verified ?? false) ? 'verified' : 'unverified',
            'sms' => ($this->phone_verified ?? false) ? 'verified' : 'unverified',
            default => 'unverified',
        };
    }

    public function getBackupMethodStatus(): string
    {
        $primary = $this->preferred_channel;

        if ($primary === 'email') {
            return ($this->phone_verified ?? false) ? 'verified' : 'unverified';
        }

        if ($primary === 'sms') {
            return ($this->email_verified ?? false) ? 'verified' : 'unverified';
        }

        return 'unverified';
    }
}
