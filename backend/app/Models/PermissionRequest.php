<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermissionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'permissionId',
        'reason',
        'status',
        'approvedBy',
        'approvalReason',
        'resolvedAt',
    ];

    protected $casts = [
        'resolvedAt' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permissionId');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approvedBy');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }
}
