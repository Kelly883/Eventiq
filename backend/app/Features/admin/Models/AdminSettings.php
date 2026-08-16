<?php

namespace App\Features\admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
        'category',
        'is_editable',
        'last_modified_by',
        'last_modified_at',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'last_modified_at' => 'datetime',
        'setting_value' => 'array',
    ];
}

