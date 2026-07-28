<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

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
    }

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
        'entity',
        'entity_id',
        'changes',
        'request_id',
    ];

    protected $casts = [
        'oldValue' => 'array',
        'newValue' => 'array',
        'metadata' => 'array',
        'changes' => 'array',
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
