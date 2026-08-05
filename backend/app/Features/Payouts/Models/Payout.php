<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'event_id',
        'settlement_policy_id',
        'amount',
        'currency',
        'status',
        'payout_method',
        'transaction_id',
        'processed_at',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function settlementPolicy(): BelongsTo
    {
        return $this->belongsTo(SettlementPolicy::class);
    }

    public function calculation(): HasOne
    {
        return $this->hasOne(PayoutCalculation::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();
    }

    public function markAsCompleted(string $transactionId): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->transaction_id = $transactionId;
        $this->processed_at = now();
        $this->save();
    }

    public function markAsFailed(string $notes = null): void
    {
        $this->status = self::STATUS_FAILED;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }
}
