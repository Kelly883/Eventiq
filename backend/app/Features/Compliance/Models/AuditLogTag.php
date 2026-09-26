<?php

namespace App\Features\Compliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogTag extends Model
{
    protected $table = 'audit_log_tags';

    protected $fillable = [
        'audit_log_id',
        'tag',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id');
    }
}
