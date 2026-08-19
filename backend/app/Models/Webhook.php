<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'organizer_id',
        'url',
        'description',
        'secret',
        'subscribed_events',
        'status',
        'last_failure_at',
        'last_success_at',
        'failure_count',
        'timeout_seconds',
        'retry_policy',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'retry_policy' => 'array',
        'last_failure_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class);
    }

    public static function generateSecret(): string
    {
        return hash('sha256', Str::random(32));
    }

    public function scopeForOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
