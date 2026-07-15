<?php

namespace App\Features\EmailNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'body', 'mjml_source'];
}
