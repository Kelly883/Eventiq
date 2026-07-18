<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'name',
        'key_prefix',
        'hashed_key',
        'scopes',
        'revoked_at',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'hashed_key',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }
}
