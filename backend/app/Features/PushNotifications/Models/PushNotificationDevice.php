<?php

namespace App\Features\PushNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationDevice extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'fcm_token', 'platform', 'last_used_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];
}
