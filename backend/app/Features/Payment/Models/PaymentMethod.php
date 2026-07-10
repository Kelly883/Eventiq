<?php

namespace App\Features\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'gateway',
        'type',
        'provider_reference',
        'metadata',
    ];
}

