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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permissionId');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approvedBy');
    }
}
