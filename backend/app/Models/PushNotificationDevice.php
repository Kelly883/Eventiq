<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationDevice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'token',
        'provider',
        'device_type',
        'offline_enabled',
        'last_sync_at',
    ];

    protected $casts = [
        'offline_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastSyncedMinutesAgo(): ?int
    {
        if ($this->last_sync_at === null) {
            return null;
        }

        return now()->diffInMinutes($this->last_sync_at);
    }
}
