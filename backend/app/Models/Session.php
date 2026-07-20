<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = null;

    protected $table = 'sessions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'userId',
        'token',
        'expiresAt',
        'createdAt',
        'revokedAt',
    ];

    protected $casts = [
        'expiresAt' => 'datetime',
        'createdAt' => 'datetime',
        'revokedAt' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}