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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
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
            'refund_requested' => 'Refund Requested',
            'refund_approved' => 'Refund Approved',
            'refund_rejected' => 'Refund Rejected',
            'payout_approved' => 'Payout Approved',
            'payout_rejected' => 'Payout Rejected',
            'ticket_checked_in' => 'Ticket Checked In',
            'ticket_voided' => 'Ticket Voided',
            'fraud_flagged' => 'Fraud Flagged',
            'fraud_approved' => 'Fraud Approved',
            'admin_setting_changed' => 'Admin Setting Changed',
            'user_permission_changed' => 'User Permission Changed',
            'data_export_requested' => 'Data Export Requested',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
