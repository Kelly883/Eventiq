<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

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

    public function use(string $ipAddress = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress,
        ]);
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
}
