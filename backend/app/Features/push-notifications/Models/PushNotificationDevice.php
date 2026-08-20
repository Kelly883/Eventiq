<?php

namespace App\Features\PushNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PushNotificationDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'token',
        'provider',
        'device_type',
        'device_name',
        'model',
        'app_version',
        'os_version',
        'locale',
        'timezone',
        'last_error',
        'error_count',
        'last_used_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'device_type' => 'string',
        'last_used_at' => 'datetime',
        'error_count' => 'integer',
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

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function recordError(string $error): void
    {
        $this->update([
            'last_error' => $error,
            'error_count' => $this->error_count + 1,
        ]);
    }
}
