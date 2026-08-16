<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            throw new \RuntimeException('audit_logs is immutable and cannot be updated.');
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
}
