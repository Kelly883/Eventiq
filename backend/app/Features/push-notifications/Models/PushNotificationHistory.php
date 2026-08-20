<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationHistory extends Model
{
    use HasFactory;

    protected $table = 'push_notification_history';

    protected $fillable = [
        'user_id',
        'device_id',
        'template_id',
        'title',
        'body',
        'data',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'error_message',
        'gateway_response',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(PushNotificationDevice::class, 'device_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PushNotificationTemplate::class, 'template_id');
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
