<?php

namespace App\Features\PushNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'title',
        'body',
        'variables',
        'is_active',
        'priority',
        'badge',
        'sound',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
        'badge' => 'integer',
    ];
}
