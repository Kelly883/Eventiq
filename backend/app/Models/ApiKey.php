<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class ApiKey extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'organizer_id',
        'name',
        'description',
        'key_prefix',
        'hashed_key',
        'scopes',
        'revoked_at',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'rate_limit',
        'rate_limit_period',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'hashed_key',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public static function generateKey(): string
    {
        return Str::random(32);
    }

    public function checkKey(string $rawKey): bool
    {
        return Hash::check($rawKey, $this->hashed_key);
    }

    public function isRateLimited(int $requestCount): bool
    {
        if (! $this->rate_limit || ! $this->rate_limit_period) {
            return false;
        }

        return $requestCount >= $this->rate_limit;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    public function rotate(): self
    {
        $this->revoke();

        return self::create([
            'organizer_id' => $this->organizer_id,
            'name' => $this->name,
            'description' => $this->description,
            'key_prefix' => substr(Str::random(32), 0, 8),
            'hashed_key' => Hash::make(Str::random(32)),
            'scopes' => $this->scopes,
            'expires_at' => $this->expires_at,
            'rate_limit' => $this->rate_limit,
            'rate_limit_period' => $this->rate_limit_period,
        ]);
    }

    public function use(string $ipAddress = null): void
    {
        $query = self::whereKey($this->getKey());

        if (! $this->usesTimestamps) {
            $query->update([
                'last_used_at' => now(),
                'last_used_ip' => $ipAddress,
            ]);
        } else {
            $query->update([
                'last_used_at' => now(),
                'last_used_ip' => $ipAddress,
                'updated_at' => now(),
            ]);

            $this->last_used_at = now();
            $this->last_used_ip = $ipAddress;
            $this->updated_at = now();
            $this->syncOriginal();
        }
    }

    public function scopeForOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByScope($query, string $scope)
    {
        return $query->whereJsonContains('scopes', $scope);
    }

    public static function findByRawKey(string $rawKey): ?static
    {
        $prefix = substr($rawKey, 0, 8);

        return self::query()
            ->where('key_prefix', $prefix)
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->select(['id', 'organizer_id', 'name', 'key_prefix', 'hashed_key', 'scopes', 'expires_at', 'revoked_at', 'rate_limit', 'rate_limit_period'])
            ->get()
            ->first(function (self $key) use ($rawKey) {
                return $key->checkKey($rawKey);
            });
    }

    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
