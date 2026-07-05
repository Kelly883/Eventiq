<?php

namespace App\Features\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTimeline extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'timestamp', 'sales_count', 'revenue'];
}
