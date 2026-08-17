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
            $title = str_replace('{{' . $key . '}}', $value, $title);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return $title . ': ' . $body;
    }
}
