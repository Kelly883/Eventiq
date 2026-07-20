<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetToken extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = null;

    protected $table = 'password_reset_tokens';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'userId',
        'token',
        'expiresAt',
        'usedAt',
        'createdAt',
    ];

    protected $casts = [
        'expiresAt' => 'datetime',
        'usedAt' => 'datetime',
        'createdAt' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}