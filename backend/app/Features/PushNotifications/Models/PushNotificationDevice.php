<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class PushNotificationDevice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'token',
        'provider',
        'device_type',
        'offline_enabled',
        'last_sync_at',
        'device_name',
        'model',
        'app_version',
        'os_version',
        'locale',
        'timezone',
        'last_error',
        'error_count',
        'last_used_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'device_type' => 'string',
        'offline_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_used_at' => 'datetime',
        'error_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function lastSyncedMinutesAgo(): ?int
    {
        if ($this->last_sync_at === null) {
            return null;
        }

        return now()->diffInMinutes($this->last_sync_at);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function recordError(string $error): void
    {
        $this->update([
            'last_error' => $error,
            'error_count' => $this->error_count + 1,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            Validator::validate([
                'token' => $model->token,
                'user_id' => $model->user_id,
                'offline_enabled' => $model->offline_enabled,
            ], [
                'token' => ['required', 'string', 'max:255', 'unique:push_notification_devices,token'],
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
