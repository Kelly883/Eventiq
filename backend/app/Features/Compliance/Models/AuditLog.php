<?php

namespace App\Features\Compliance\Models;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Compliance\Enums\AuditLogTargetType;
use App\Features\Compliance\Enums\AuditLogStatus;
use App\Features\Compliance\Enums\ComplianceClassification;
use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payouts\Models\Payout;
use App\Features\Refunds\Models\RefundRequest;
use App\Features\Ticketing\Models\Ticket;
use App\Features\admin\Models\AdminSettings;
use App\Models\Event;
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
        'user_id',
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

    public function targetEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'target_id');
    }

    public function targetOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'target_id');
    }

    public function targetPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'target_id');
    }

    public function targetPayout(): BelongsTo
    {
        return $this->belongsTo(Payout::class, 'target_id');
    }

    public function targetRefund(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class, 'target_id');
    }

    public function targetTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'target_id');
    }

    public function targetSetting(): BelongsTo
    {
        return $this->belongsTo(AdminSettings::class, 'target_id');
    }

    public function getTargetName(): ?string
    {
        $type = $this->target_type instanceof \BackedEnum ? $this->target_type->value : (string) $this->target_type;

        return match ($type) {
            'user' => $this->targetUser->name ?? $this->targetUser()->value('name'),
            'event' => $this->targetEvent->title ?? $this->targetEvent()->value('title'),
            'order' => $this->targetOrder->order_number ?? $this->targetOrder()->value('order_number'),
            'payout' => $this->targetPayout->id ?? $this->targetPayout()->value('id'),
            'refund' => $this->targetRefund->id ?? $this->targetRefund()->value('id'),
            'payment' => $this->targetPayment->id ?? $this->targetPayment()->value('id'),
            'setting' => $this->targetSetting->setting_key ?? $this->targetSetting()->value('setting_key'),
            'ticket' => $this->targetTicket->ticket_id ?? $this->targetTicket()->value('ticket_id'),
            default => (string) $this->target_id,
        };
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFilter($query, array $filters = [], ?int $perPage = null)
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

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($perPage)) {
            $query->paginate($perPage);
        }

        return $query;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('changed_fields', 'like', "%{$term}%");
        });
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

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeWithTargetDetails($query)
    {
        return $query->with([
            'user',
            'targetUser',
            'targetEvent',
            'targetOrder',
            'targetPayment',
            'targetPayout',
            'targetRefund',
            'targetTicket',
            'targetSetting',
        ]);
    }

    public function maskSensitiveData(): array
    {
        $data = $this->toArray();

        if (!empty($data['geolocation']['ip_address'])) {
            $ip = $data['geolocation']['ip_address'];

            if (str_contains($ip, ':')) {
                $parts = explode(':', $ip);
                $data['geolocation']['ip_address'] = $parts[0] . ':xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';
            } else {
                $parts = explode('.', $ip);
                if (count($parts) === 4) {
                    $data['geolocation']['ip_address'] = $parts[0] . '.xxx.xxx.xxx';
                }
            }
        }

        unset($data['request_data']);
        unset($data['response_data']);

        return $data;
    }
}
