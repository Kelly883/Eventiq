<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

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

    protected static function booted(): void
    {
        static::creating(function ($model) {
            Validator::validate([
                'token' => $model->token,
                'user_id' => $model->user_id,
                'offline_enabled' => $model->offline_enabled,
            ], [
                'token' => ['required', 'string', 'max:255'],
                'user_id' => ['required', 'string', 'exists:users,id'],
                'offline_enabled' => ['required', 'boolean'],
            ]);
        });

        static::updating(function ($model) {
            if ($model->isDirty('created_at')) {
                throw new \RuntimeException('created_at is immutable and cannot be changed.');
            }
        });
    }
}
