<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessibilityPreference extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'font_size',
        'high_contrast',
        'screen_reader_optimized',
        'focus_indicator_enhanced',
        'motion_reduced',
        'line_height',
        'letter_spacing',
        'word_spacing',
        'color_blindness_mode',
    ];

    protected $casts = [
        'high_contrast' => 'boolean',
        'screen_reader_optimized' => 'boolean',
        'focus_indicator_enhanced' => 'boolean',
        'motion_reduced' => 'boolean',
        'line_height' => 'decimal:2',
        'letter_spacing' => 'decimal:3',
        'word_spacing' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
