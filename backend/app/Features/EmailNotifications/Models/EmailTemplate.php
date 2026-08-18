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
        'from_name',
        'from_email',
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
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
        $html = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/is', '', $html);
        $html = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/is', '', $html);

        $html = preg_replace('/\s*on\w+\s*=\s*("|\')(?:\\\\\1|.)*?\1/is', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^\s>]+/is', '', $html);

        $html = preg_replace('/<svg\b[^<]*(?:(?!<\/svg>)<[^<]*)*<\/svg>/is', '', $html);

        $html = preg_replace('/\s(style|href|src)\s*=\s*("|\')(?:\\\\\2|.)*?\2/is', '', $html);

        $allowedTags = '<a><abbr><acronym><b><blockquote><br><caption><cite><code><col><colgroup><dd><del><dfn><dl><dt><em><h1><h2><h3><h4><h5><h6><hr><i><img><ins><kbd><li><mark><ol><p><pre><q><s><samp><small><span><strike><strong><sub><sup><table><tbody><td><tfoot><th><thead><tr><u><ul><var>';
        $html = strip_tags($html, $allowedTags);

        return $html;
    }

    public function render(array $data = []): string
    {
        $subject = $this->subject;
        $body = $this->html_body;

        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $subject);
            $body = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $body);
        }

        return $subject . "\n\n" . $body;
    }

    public function validateVariables(): bool
    {
        if (!is_array($this->variables)) {
            return false;
        }

        foreach ($this->variables as $variable) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                return false;
            }
        }

        return true;
    }
}
