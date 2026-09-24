<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PushNotificationDevice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'token_encrypted',
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

    /**
     * Create a new factory instance for the model.
     */
    public static function newFactory()
    {
        return \Database\Factories\PushNotificationDeviceFactory::new();
    }

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

    public function scopeByTokenHash($query, string $hash)
    {
        return $query->where('token_hash', $hash);
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

    /**
     * Get the decrypted token for Firebase use.
     */
    public function getDecryptedToken(): string
    {
        if (!empty($this->token_encrypted)) {
            return Crypt::decryptString($this->token_encrypted);
        }

        return $this->token;
    }

    /**
     * Set the token — stores both plaintext (for compatibility) and encrypted.
     */
    public function setTokenAttribute($value): void
    {
        $this->attributes['token'] = $value;
        $this->attributes['token_hash'] = Hash::make($value);
        $this->attributes['token_encrypted'] = Crypt::encryptString($value);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            // Generate token hash for lookups
            if (empty($model->token_hash) && !empty($model->token)) {
                $model->token_hash = hash('sha256', $model->token);
            }
            // Encrypt token for storage
            if (empty($model->token_encrypted) && !empty($model->token)) {
                $model->token_encrypted = Crypt::encryptString($model->token);
            }

            Validator::validate([
                'token' => $model->token,
                'user_id' => $model->user_id,
            ], [
                'token' => ['required', 'string', 'max:255', 'unique:push_notification_devices,token'],
                'user_id' => ['required', 'string', 'exists:users,id'],
            ]);
        });

        static::updating(function ($model) {
            if ($model->isDirty('created_at')) {
                throw new \RuntimeException('created_at is immutable and cannot be changed.');
            }

            // Re-hash and re-encrypt if token changed
            if ($model->isDirty('token')) {
                $model->token_hash = hash('sha256', $model->token);
                $model->token_encrypted = Crypt::encryptString($model->token);
            }
        });
    }
}
