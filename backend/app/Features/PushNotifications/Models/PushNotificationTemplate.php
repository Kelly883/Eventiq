<?php

namespace App\Features\PushNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'title',
        'body',
        'variables',
        'is_active',
        'priority',
        'badge',
        'sound',
        'click_action',
        'collapse_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
        'badge' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function render(array $variables = []): string
    {
        $title = $this->title;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $title = str_replace($placeholder, $value, $title);
            $body = str_replace($placeholder, $value, $body);
        }

        $missingVariables = [];
        foreach ($this->variables ?? [] as $variable) {
            $placeholder = '{{' . $variable . '}}';
            if (str_contains($title, $placeholder) || str_contains($body, $placeholder)) {
                $missingVariables[] = $variable;
            }
        }

        if ($missingVariables !== []) {
            \Illuminate\Support\Facades\Log::warning('PushNotificationTemplate: Missing variables: ' . implode(', ', $missingVariables));
        }

        return trim($title) . ': ' . trim($body);
    }
}
