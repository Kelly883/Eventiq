<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryPreferences extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_preferences';

    protected $fillable = [
        'user_id',
        'push_notifications_enabled',
        'push_order_confirmation',
        'push_event_reminder',
        'push_checkin_alert',
        'push_promotional_offers',
    ];

    protected $casts = [
        'push_notifications_enabled' => 'boolean',
        'push_order_confirmation' => 'boolean',
        'push_event_reminder' => 'boolean',
        'push_checkin_alert' => 'boolean',
        'push_promotional_offers' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isNotificationTypeEnabled(string $type): bool
    {
        $field = 'push_' . $type;

        return $this->push_notifications_enabled && (bool) $this->{$field};
    }
}
