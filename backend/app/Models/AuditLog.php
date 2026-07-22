<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'adminId',
        'targetUserId',
        'action',
        'oldValue',
        'newValue',
        'reason',
        'metadata',
        'event_id',
        'user_id',
        'ticket_id',
        'details',
    ];

    protected $casts = [
        'oldValue' => 'array',
        'newValue' => 'array',
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adminId');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'targetUserId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
