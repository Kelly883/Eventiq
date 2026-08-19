<?php

namespace App\Features\admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminSettings extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AdminSettings $setting) {
            if (empty($setting->{$setting->getKeyName()})) {
                $setting->{$setting->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $table = 'admin_settings';

    protected $fillable = [
        'id',
        'setting_key',
        'setting_value',
        'description',
        'category',
        'is_editable',
        'last_modified_by',
        'last_modified_at',
    ];

    protected $casts = [
        'setting_value' => 'array',
        'is_editable' => 'boolean',
        'last_modified_at' => 'datetime',
    ];

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('setting_key', $key);
    }
}
