<?php

namespace App\Features\PushNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationDevice extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'token', 'provider', 'device_type', 'last_used_at'];

    protected $casts = [
        'device_type' => 'string',
        'last_used_at' => 'datetime',
    ];
}
