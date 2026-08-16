<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessedWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'gateway_reference',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
