<?php

namespace App\Features\Payment\Models;

use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrganizerPayout extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'organizer_id',
        'gateway',
        'reference',
        'status',
        'amount',
        'fees',
        'net_amount',
        'currency',
        'metadata',
        'paid_at',
        'failure_reason',
        'initiated_by',
        'approved_by',
        'approved_at',
        'settlement_id',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'fees' => 'integer',
        'net_amount' => 'integer',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Payouts\Models\Payout::class, 'settlement_id');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function getAmountInMajorUnits(): float
    {
        return (float) $this->amount / 100;
    }

    public function getNetAmountInMajorUnits(): float
    {
        return (float) $this->net_amount / 100;
    }
}
