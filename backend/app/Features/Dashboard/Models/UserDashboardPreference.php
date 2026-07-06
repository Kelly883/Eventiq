<?php

namespace App\Features\Dashboard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'preferences'];
    protected $casts = ['preferences' => 'array'];
}
