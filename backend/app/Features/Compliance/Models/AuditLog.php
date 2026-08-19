<?php

namespace App\Features\Compliance\Models;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Compliance\Enums\AuditLogTargetType;
use App\Features\Compliance\Enums\AuditLogStatus;
use App\Features\Compliance\Enums\ComplianceClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'audit_logs';

    protected $fillable = [
        'action',
        'target_type',
        'target_id',
        'description',
        'geolocation',
        'request_data',
        'response_data',
        'changed_fields',
        'status',
        'compliance_classification',
        'metadata',
    ];

    protected $casts = [
        'action' => AuditLogAction::class,
        'target_type' => AuditLogTargetType::class,
        'status' => AuditLogStatus::class,
        'compliance_classification' => ComplianceClassification::class,
        'geolocation' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->user_id)) {
                throw new \InvalidArgumentException('user_id is required and immutable.');
            }

            $model->truncateLargeJsonFields();
        });

        static::updating(function ($model) {
            if ($model->isDirty('user_id')) {
                throw new \RuntimeException('user_id is immutable and cannot be changed.');
            }

            if ($model->isDirty('created_at')) {
                throw new \RuntimeException('created_at is immutable and cannot be changed.');
            }
        });
    }

    protected function truncateLargeJsonFields(): void
    {
        $maxBytes = 1024 * 1024; // 1MB per field

        foreach (['geolocation', 'request_data', 'response_data', 'changed_fields', 'metadata'] as $field) {
            if (!is_array($this->{$field})) {
                continue;
            }

            $json = json_encode($this->{$field}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false || strlen($json) > $maxBytes) {
                $this->{$field} = [
                    'truncated' => true,
                    'original_size_bytes' => $json !== false ? strlen($json) : 0,
                    'preview' => array_slice($this->{$field}, 0, 5),
                ];
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function getTargetName(): ?string
    {
        return match ($this->target_type) {
            'user' => $this->targetUser->name ?? null,
            'event' => \App\Models\Event::find($this->target_id)?->title,
            'order' => \App\Features\Checkout\Models\Order::find($this->target_id)?->order_number,
            'payout' => \App\Features\Payouts\Models\Payout::find($this->target_id)?->id,
            'refund' => \App\Features\Refunds\Models\RefundRequest::find($this->target_id)?->id,
            'payment' => \App\Features\Checkout\Models\Payment::find($this->target_id)?->id,
            'setting' => \App\Features\admin\Models\AdminSettings::find($this->target_id)?->setting_key,
            'ticket' => \App\Features\Ticketing\Models\Ticket::find($this->target_id)?->ticket_id,
            default => null,
        };
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFilter($query, array $filters = [])
    {
        if (!empty($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (!empty($filters['target_type'])) {
            $query->byTargetType($filters['target_type']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['classification'])) {
            $query->byClassification($filters['classification']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->forDateRange($filters['start_date'], $filters['end_date']);
        }

        return $query;
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByTargetType($query, string $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByClassification($query, string $classification)
    {
        return $query->where('compliance_classification', $classification);
    }

    public function maskSensitiveData(): array
    {
        $data = $this->toArray();

        if (isset($data['geolocation']['ip_address'])) {
            $parts = explode('.', $data['geolocation']['ip_address']);
            $data['geolocation']['ip_address'] = $parts[0] . '.xxx.xxx.xxx';
        }

        unset($data['request_data']);
        unset($data['response_data']);

        return $data;
    }
}
