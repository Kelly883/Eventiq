<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PushNotificationDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'token', 'provider', 'device_type', 'last_used_at'];

    protected $casts = [
        'device_type' => 'string',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
