<?php

namespace App\Features\EmailNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'subject',
        'body',
        'html_body',
        'mjml_body',
        'variables',
        'is_active',
        'published_at',
        'version',
        'category',
        'description',
        'preview_html',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];
}
