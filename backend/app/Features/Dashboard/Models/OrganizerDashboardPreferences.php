<?php

namespace App\Features\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizerDashboardPreferences extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'preferences'];
    protected $casts = ['preferences' => 'array'];
}
