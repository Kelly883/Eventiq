<?php

namespace App\Features\EmailNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\HTML;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'subject',
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
        'variables' => 'array',
    ];

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function setHtmlBodyAttribute($value): void
    {
        $this->attributes['html_body'] = $this->sanitizeHtmlBody($value);
    }

    public function sanitizeHtmlBody(string $html): string
    {
        return HTML::entities($html);
    }
}
