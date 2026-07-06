<?php

namespace App\Features\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Structure-first: reuse the platform audit_logs table shape.
// NOTE: If you already use App\Models\AuditLog, you can replace references.
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'action',
        'entity',
        'entity_id',
        'changes',
        'user_id',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}

