<?php

namespace App\Features\OfflineSync\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSyncInboxItem extends Model
{
    protected $table = 'offline_sync_inbox';

    protected $fillable = [
        'client_id',
        'op_type',
        'entity_id',
        'client_mutation_id',
        'status',
        'attempts',
        'next_retry_at',
        'payload',
        'client_context',
        'applied_revision',
        'server_state',
        'error_message',
        'applied_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'client_context' => 'array',
        'server_state' => 'array',
        'next_retry_at' => 'datetime',
        'applied_at' => 'datetime',
    ];
}

