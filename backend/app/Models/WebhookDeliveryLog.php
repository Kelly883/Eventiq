<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'webhook_id',
        'event',
        'attempt_number',
        'payload',
        'status',
        'response_code',
        'response_body',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function scopeByWebhook($query, string $webhookId)
    {
        return $query->where('webhook_id', $webhookId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'error']);
    }

    public function scopeByAttempt($query, int $attemptNumber)
    {
        return $query->where('attempt_number', $attemptNumber);
    }

    public function scopeLatestAttempt($query)
    {
        return $query->orderByDesc('attempt_number');
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeGroupedByEvent($query)
    {
        return $query->selectRaw('event, COUNT(*) as total, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count, SUM(CASE WHEN status IN ("failed", "error") THEN 1 ELSE 0 END) as failure_count, AVG(duration_ms) as avg_duration_ms')
            ->groupBy('event');
    }
}
