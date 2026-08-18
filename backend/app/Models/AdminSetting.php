<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSetting extends Model
{
    use HasFactory;

    protected $table = 'admin_settings';

    protected $fillable = [
        'settingKey',
        'settingValue',
        'description',
        'category',
        'isEditable',
        'lastModifiedBy',
    ];

    protected $casts = [
        'settingValue' => 'json',
        'isEditable' => 'boolean',
    ];

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lastModifiedBy');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeEditable($query)
    {
        return $query->where('isEditable', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('settingKey', $key);
    }
}
