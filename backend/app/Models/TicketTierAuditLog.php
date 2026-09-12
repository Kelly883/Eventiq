<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTierAuditLog extends Model
{
    protected $table = 'ticket_tier_audit_logs';

    protected $fillable = [
        'event_id',
        'tier_id',
        'action',
        'user_id',
        'organizer_id',
        'changes',
        'tier_name',
        'tier_price',
    ];

    protected $casts = [
        'changes' => 'array',
        'tier_price' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }

    public static function logCreate(int $eventId, int $tierId, string $tierName, float $tierPrice, int $userId, int $organizerId): self
    {
        return self::create([
            'event_id' => $eventId,
            'tier_id' => $tierId,
            'action' => 'created',
            'user_id' => $userId,
            'organizer_id' => $organizerId,
            'changes' => ['name' => $tierName, 'price' => $tierPrice],
            'tier_name' => $tierName,
            'tier_price' => $tierPrice,
        ]);
    }

    public static function logUpdate(int $eventId, int $tierId, string $tierName, float $tierPrice, int $userId, int $organizerId, array $oldValues, array $newValues): self
    {
        return self::create([
            'event_id' => $eventId,
            'tier_id' => $tierId,
            'action' => 'updated',
            'user_id' => $userId,
            'organizer_id' => $organizerId,
            'changes' => [
                'old' => $oldValues,
                'new' => $newValues,
            ],
            'tier_name' => $tierName,
            'tier_price' => $tierPrice,
        ]);
    }

    public static function logDelete(int $eventId, int $tierId, string $tierName, float $tierPrice, int $userId, int $organizerId): self
    {
        return self::create([
            'event_id' => $eventId,
            'tier_id' => $tierId,
            'action' => 'deleted',
            'user_id' => $userId,
            'organizer_id' => $organizerId,
            'changes' => ['name' => $tierName, 'price' => $tierPrice],
            'tier_name' => $tierName,
            'tier_price' => $tierPrice,
        ]);
    }
}
