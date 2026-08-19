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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
