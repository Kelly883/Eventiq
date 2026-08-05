<?php

namespace App\Features\Fraud\Models;

use App\Models\User;
use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fraud event model - IMMUTABLE audit trail.
 *
 * ⚠️ CRITICAL: This model does NOT use SoftDeletes.
 * Fraud records must never be deletable. If a record needs to be hidden,
 * transition status to an audit-safe state instead of deleting it.
 *
 * @property string $id
 * @property string $order_id
 * @property string $user_id
 * @property string|null $ticket_id
 * @property int|null $event_id
 * @property string $event_type
 * @property float $risk_score
 * @property string $risk_level
 * @property string $detection_method
 * @property array|null $fraud_factors
 * @property array|null $payment_details
 * @property array|null $velocity_metrics
 * @property array|null $device_info
 * @property array|null $duplicate_ticket_info
 * @property string|null $detected_at
 * @property string|null $first_check_in_at
 * @property string|null $first_check_in_by
 * @property string|null $second_check_in_at
 * @property string|null $second_check_in_by
 * @property string $status
 * @property string|null $reviewed_by
 * @property string|null $review_notes
 * @property string|null $reviewed_at
 * @property string|null $notes
 * @property string|null $session_id
 * @property string|null $ip_address
 * @property string|null $card_fingerprint
 * @property float|null $amount
 * @property string|null $currency
 * @property string|null $gateway_response_code
 * @property string|null $automated_action_taken
 * @property string|null $source
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FraudEvent extends Model
{
    use HasUuids;

    protected $table = 'fraud_events';

    protected $fillable = [
        'order_id',
        'user_id',
        'ticket_id',
        'event_id',
        'event_type',
        'risk_score',
        'risk_level',
        'detection_method',
        'fraud_factors',
        'payment_details',
        'velocity_metrics',
        'device_info',
        'duplicate_ticket_info',
        'detected_at',
        'first_check_in_at',
        'first_check_in_by',
        'second_check_in_at',
        'second_check_in_by',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'notes',
        'session_id',
        'ip_address',
        'card_fingerprint',
        'amount',
        'currency',
        'gateway_response_code',
        'automated_action_taken',
        'source',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'fraud_factors' => 'array',
        'payment_details' => 'array',
        'velocity_metrics' => 'array',
        'device_info' => 'array',
        'duplicate_ticket_info' => 'array',
        'amount' => 'decimal:2',
        'detected_at' => 'datetime',
        'first_check_in_at' => 'datetime',
        'second_check_in_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function firstCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_check_in_by');
    }

    public function secondCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_check_in_by');
    }

    /**
     * Scope a query to only include events with a given status.
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include events with a minimum risk score.
     */
    public function scopeMinRiskScore($query, float $score)
    {
        return $query->where('risk_score', '>=', $score);
    }

    /**
     * Scope a query to only include events within a date range.
     */
    public function scopeWhereCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
