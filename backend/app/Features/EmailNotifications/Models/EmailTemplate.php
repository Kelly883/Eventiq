<?php

namespace App\Features\EmailNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'subject',
        'body',
        'html_body',
        'mjml_body',
        'variables',
        'is_active',
        'version',
        'category',
        'description',
        'preview_html',
    ];
}
