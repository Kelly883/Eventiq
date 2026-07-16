<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'total_amount',
        'currency',
        'payment_gateway',
        'payment_reference',
        'device_id',
        'ip_address',
    ];
}
