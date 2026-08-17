<?php

namespace App\Features\Fraud\Models;

use App\Models\User;
use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fraud event model - audit trail with soft deletes.
 *
 * Fraud records support soft deletion for audit retention.
 * Hard deletes are prevented by database constraints.
 *
 * @property string $id
 * @property string $order_id
 * @property string $user_id
 * @property string|null $ticket_id
 * @property string|null $event_id
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
 * @property string|null $reviewed_by_id
 * @property string|null $reviewed_by_type
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
 * @property string|null $payment_intent_id
 * @property bool $chargeback_flag
 * @property string|null $authentication_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FraudEvent extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'fraud_events';

    protected $fillable = [
        'order_id',
        'user_id',
        'user_email',
        'ticket_id',
        'event_id',
        'fraud_type',
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
        'reviewed_by_id',
        'reviewed_by_type',
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
        'payment_intent_id',
        'chargeback_flag',
        'authentication_method',
        'card_country',
        'device_fingerprint',
        'payment_method',
        'payment_gateway',
        'user_orders_last_24h',
        'user_spend_last_24h',
        'user_agent',
        'referrer',
        'promo_code',
        'escalated_to',
        'escalated_at',
        'resolution',
        'evidence_snapshot',
        'is_archived',
        'archived_at',
        'order_total',
        'ticket_quantity',
        'billing_country',
        'billing_zip',
        'shipping_billing_match',
        'order_status',
        'device_type',
        'proxy_vpn_detected',
        'ip_reputation_score',
        'account_age_days',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'fraud_factors' => 'array',
        'payment_details' => 'array',
        'velocity_metrics' => 'array',
        'device_info' => 'array',
        'duplicate_ticket_info' => 'array',
        'evidence_snapshot' => 'array',
        'amount' => 'decimal:2',
        'user_spend_last_24h' => 'decimal:2',
        'order_total' => 'decimal:2',
        'detected_at' => 'datetime',
        'first_check_in_at' => 'datetime',
        'second_check_in_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'escalated_at' => 'datetime',
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_archived' => 'boolean',
        'shipping_billing_match' => 'boolean',
        'proxy_vpn_detected' => 'boolean',
        'chargeback_flag' => 'boolean',
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

    public function reviewedByUser(): MorphTo
    {
        return $this->morphTo();
    }

    public function firstCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_check_in_by');
    }

    public function secondCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_check_in_by');
    }

    public function getRiskLevelBadgeColor(): string
    {
        return match ($this->risk_level) {
            'low' => 'green',
            'medium' => 'amber',
            'high' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedRiskScore(): string
    {
        return number_format((float) $this->risk_score, 2);
    }

    public function getReadableEventType(): string
    {
        return match ($this->fraud_type) {
            'suspicious_login' => 'Suspicious Login',
            'multiple_failed_payments' => 'Multiple Failed Payments',
            'unusual_location' => 'Unusual Location',
            'ticket_scalping' => 'Ticket Scalping',
            'duplicate_order' => 'Duplicate Order',
            'high_velocity_purchases' => 'High Velocity Purchases',
            'blacklisted_ip' => 'Blacklisted IP',
            'stolen_card' => 'Stolen Card',
            'chargeback_risk' => 'Chargeback Risk',
            'identity_mismatch' => 'Identity Mismatch',
            default => ucfirst(str_replace('_', ' ', $this->fraud_type)),
        };
    }

    public function scopeFlagged($query)
    {
        return $query->where('status', 'flagged');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeAutoBlocked($query)
    {
        return $query->where('status', 'auto_blocked');
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'high');
    }

    public function scopeMediumRisk($query)
    {
        return $query->where('risk_level', 'medium');
    }

    public function scopeLowRisk($query)
    {
        return $query->where('risk_level', 'low');
    }

    public function scopeCreatedInDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeHighRiskScore($query, float $threshold)
    {
        return $query->where('risk_score', '>=', $threshold);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
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
        return $query->where('fraud_type', $eventType);
    }
}
