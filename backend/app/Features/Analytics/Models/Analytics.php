<?php

namespace App\Features\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Analytics extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'total_sales', 'total_revenue', 'tickets_sold'];
}
