<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Relation;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        Relation::morphMap([
            'user' => User::class,
            'event' => Event::class,
        ]);

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            $dirty = $model->getDirty();
            unset($dirty['updated_at'], $dirty['deleted_at']);
            if ($model->isDirty('deleted_at') && $model->deleted_at !== null) {
                return;
            }
            if (!empty($dirty)) {
                throw new \RuntimeException('audit_logs is immutable and cannot be updated.');
            }
        });
    }

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'status',
        'ip_address',
        'source',
        'user_agent',
        'geolocation',
        'request_data',
        'response_data',
        'changed_fields',
        'error_message',
        'error_code',
        'compliance_classification',
        'retention_date',
        'retention_reason',
        'metadata',
        'description',
    ];

    protected $casts = [
        'geolocation' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'retention_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
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

    public function scopeByAdmin($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithRetentionExpired($query, $before = null)
    {
        $before = $before ?? now();

        return $query->where('retention_date', '<', $before);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('action', 'like', '%'.$term.'%')
                ->orWhere('target_type', 'like', '%'.$term.'%')
                ->orWhere('ip_address', 'like', '%'.$term.'%')
                ->orWhere('source', 'like', '%'.$term.'%')
                ->orWhere('error_message', 'like', '%'.$term.'%')
                ->orWhere('error_code', 'like', '%'.$term.'%');
        });
    }

    public function scopeGroupByAction($query)
    {
        return $query->selectRaw('action, COUNT(*) as count, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count, SUM(CASE WHEN status = "failure" THEN 1 ELSE 0 END) as failure_count')
            ->groupBy('action');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeFilter($query, array $filters = [], ?int $perPage = null)
    {
        if (! empty($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (! empty($filters['target_type'])) {
            $query->byTargetType($filters['target_type']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['classification'])) {
            $query->byClassification($filters['classification']);
        }

        if (! empty($filters['from']) && ! empty($filters['to'])) {
            $query->forDateRange($filters['from'], $filters['to']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (! empty($perPage)) {
            $query->paginate($perPage);
        }

        return $query;
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failure' => 'red',
            'warning' => 'amber',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'success' => 'Success',
            'failure' => 'Failed',
            'warning' => 'Warning',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getTargetName(): ?string
    {
        $type = $this->target_type;
        $targetId = $this->target_id;

        if (empty($type) || empty($targetId)) {
            return null;
        }

        return match ($type) {
            'user' => User::select('id', 'name')->where('id', $targetId)->value('name'),
            'event' => Event::select('id', 'title')->where('id', $targetId)->value('title'),
            default => (string) $targetId,
        };
    }

    public function maskSensitiveData(): array
    {
        $data = $this->toArray();

        if (!empty($data['ip_address'])) {
            $parts = explode('.', $data['ip_address']);
            if (count($parts) === 4) {
                $data['ip_address'] = $parts[0] . '.xxx.xxx.xxx';
            }
        }

        $data['request_data'] = '[REDACTED]';
        $data['response_data'] = '[REDACTED]';

        return $data;
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            'user_login' => 'User Login',
            'user_logout' => 'User Logout',
            'user_suspended' => 'User Suspended',
            'event_created' => 'Event Created',
            'event_approved' => 'Event Approved',
            'event_flagged' => 'Event Flagged',
            'event_cancelled' => 'Event Cancelled',
            'payment_processed' => 'Payment Processed',
            'payment_refunded' => 'Payment Refunded',
            'refund.requested' => 'Refund Requested',
            'refund_approved' => 'Refund Approved',
            'refund_rejected' => 'Refund Rejected',
            'payout_approved' => 'Payout Approved',
            'payout_rejected' => 'Payout Rejected',
            'ticket_checked_in' => 'Ticket Checked In',
            'ticket_voided' => 'Ticket Voided',
            'ticket.purged' => 'Ticket Purged',
            'fraud_flagged' => 'Fraud Flagged',
            'fraud_approved' => 'Fraud Approved',
            'admin_setting_changed' => 'Admin Setting Changed',
            'user_permission_changed' => 'User Permission Changed',
            'data_export_requested' => 'Data Export Requested',
            'check_in' => 'Check In',
            default => ucfirst(str_replace(['_', '.'], ' ', $this->action)),
        };
    }
}
