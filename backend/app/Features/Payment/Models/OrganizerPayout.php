<?php

namespace App\Features\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerPayout extends Model
{
    protected $fillable = [
        'organizer_id',
        'gateway',
        'reference',
        'status',
        'amount',
        'metadata',
    ];
}

