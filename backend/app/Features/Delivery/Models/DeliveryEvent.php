<?php

namespace App\Features\Delivery\Models;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Features\Fraud\Models\FraudEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryEvent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Threshold in bytes above which payload is auto-offloaded to delivery_event_data.
     * Set to 0 to disable auto-offloading and store everything inline.
     */
    public const PAYLOAD_AUTO_OFFLOAD_THRESHOLD = 500;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'event_id',
        'order_id',
        'fraud_event_id',
        'channel',
        'status',
        'ticket_reference',
        'recipient',
        'subject',
        'body',
        'sender',
        'payload',
        'provider',
        'provider_message_id',
        'provider_response',
        'error_message',
        'attempt_count',
        'max_attempts',
        'last_attempt_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'provider_response' => 'array',
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model and register event handlers.
     *
     * Auto-offloads large payloads (> 500 bytes) to the delivery_event_data
     * table on creation, keeping the main delivery_events row slim.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (DeliveryEvent $event) {
            // Auto-offload large payloads to the separate data table
            if (static::PAYLOAD_AUTO_OFFLOAD_THRESHOLD > 0) {
                $payloadSize = 0;
                if ($event->payload) {
                    $payloadSize = strlen(json_encode($event->payload));
                }

                $hasLargePayload = $payloadSize > static::PAYLOAD_AUTO_OFFLOAD_THRESHOLD;
                $hasProviderResponse = !empty($event->getOriginal('provider_response'));
                $hasErrorMessage = !empty($event->getOriginal('error_message'));

                if ($hasLargePayload || $hasProviderResponse || $hasErrorMessage) {
                    try {
                        $event->eventData()->create([
                            'payload' => $hasLargePayload ? $event->payload : null,
                            'provider_response' => $hasProviderResponse ? $event->provider_response : null,
                            'error_message' => $hasErrorMessage ? $event->error_message : null,
                        ]);

                        // Clear inline fields to keep row size small
                        $updateData = [];
                        if ($hasLargePayload) {
                            $updateData['payload'] = null;
                        }
                        if ($hasProviderResponse) {
                            $updateData['provider_response'] = null;
                        }
                        if ($hasErrorMessage) {
                            $updateData['error_message'] = null;
                        }

                        if (!empty($updateData)) {
                            $event->updateQuietly($updateData);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning(
                            "DeliveryEvent: Failed to auto-offload payload for {$event->id}: {$e->getMessage()}"
                        );
                    }
                }
            }
        });
    }

    // ── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope a query to only include unarchived delivery events.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope a query to only include archived delivery events.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope a query to only include events older than a given date.
     */
    public function scopeOlderThan($query, $date)
    {
        return $query->where('created_at', '<', $date);
    }

    /**
     * Scope a query to only include events deliverable for retry.
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
            ->whereColumn('attempt_count', '<', 'max_attempts');
    }

    /**
     * Scope a query to only include delivery events for a given user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include delivery events with a given status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Relationships ────────────────────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * One-to-one relationship with delivery_event_data for large payloads.
     */
    public function eventData()
    {
        return $this->hasOne(DeliveryEventData::class);
    }

    public function fraudEvent()
    {
        return $this->belongsTo(FraudEvent::class, 'fraud_event_id');
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'sent', 'delivered' => 'green',
            'pending' => 'amber',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function canRetry(): bool
    {
        return $this->attempt_count < $this->max_attempts && !$this->isBlocked();
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked' || $this->status === 'void';
    }
}
