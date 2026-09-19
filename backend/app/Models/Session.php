<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = null;

    protected $table = 'sessions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'userId',
        'token',
        'expiresAt',
        'createdAt',
        'revokedAt',
        'lastActivityAt',
    ];

    protected $casts = [
        'expiresAt' => 'datetime',
        'createdAt' => 'datetime',
        'revokedAt' => 'datetime',
        'lastActivityAt' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revokedAt')->where('expiresAt', '>', now());
    }

    /**
     * Determine whether the session has been idle too long.
     * Uses SESSION_IDLE_TIMEOUT_MINUTES from config, default 30 minutes.
     */
    public function isIdleExpired(): bool
    {
        $idleTimeoutMinutes = (int) config('sanctum.idle_timeout', 30);
        if ($idleTimeoutMinutes <= 0) {
            return false; // idle timeout disabled
        }

        $lastActivity = $this->lastActivityAt ?? $this->createdAt;
        if (!$lastActivity) {
            return false; // no activity recorded — don't punish legacy sessions
        }

        return $lastActivity->copy()->addMinutes($idleTimeoutMinutes)->isPast();
    }

    /**
     * Determine whether the session is valid (not revoked, not expired, not idle).
     */
    public function isValid(): bool
    {
        return $this->revokedAt === null
            && $this->expiresAt->isFuture()
            && !$this->isIdleExpired();
    }

    /**
     * Record that this session was just used for an authenticated request.
     * Updates lastActivityAt and extends expiresAt (sliding expiration).
     */
    public function recordActivity(): void
    {
        $this->lastActivityAt = now();
        // Optional: extend the absolute expiration on activity (sliding window)
        $maxLifetime = (int) config('sanctum.expiration', 10080);
        $this->expiresAt = min(
            $this->expiresAt->copy()->addMinutes(config('sanctum.idle_timeout', 30)),
            $this->createdAt->copy()->addMinutes($maxLifetime)
        );
        $this->saveQuietly();
    }

    /**
     * Mark the session as revoked (logged out).
     */
    public function revoke(): void
    {
        $this->revokedAt = now();
        $this->saveQuietly();
    }

    public function isActive(): bool
    {
        return $this->isValid();
    }
}