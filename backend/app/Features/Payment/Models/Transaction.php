<?php

namespace App\Features\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'gateway',
        'reference',
        'status',
        'amount',
        'currency',
        'metadata',
    ];
}

