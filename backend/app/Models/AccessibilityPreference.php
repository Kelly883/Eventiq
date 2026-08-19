<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessibilityPreference extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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

    protected static array $validationRules = [
        'font_size' => 'integer|min:12|max:24',
        'line_height' => 'numeric|min:1.0|max:2.0',
        'letter_spacing' => 'numeric|min:0|max:0.2',
        'word_spacing' => 'numeric|min:0|max:0.2',
        'color_blindness_mode' => 'in:none,protanopia,deuteranopia,tritanopia',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateForUser(string $userId): static
    {
        $preference = static::where('user_id', $userId)->first();

        if ($preference) {
            return $preference;
        }

        return static::create([
            'user_id' => $userId,
            'font_size' => 16,
            'high_contrast' => false,
            'screen_reader_optimized' => false,
            'focus_indicator_enhanced' => false,
            'motion_reduced' => false,
            'line_height' => 1.5,
            'letter_spacing' => 0.0,
            'word_spacing' => 0.0,
            'color_blindness_mode' => 'none',
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'fontSize' => (int) $this->font_size,
            'highContrast' => (bool) $this->high_contrast,
            'screenReaderOptimized' => (bool) $this->screen_reader_optimized,
            'focusIndicatorEnhanced' => (bool) $this->focus_indicator_enhanced,
            'motionReduced' => (bool) $this->motion_reduced,
            'lineHeight' => (float) $this->line_height,
            'letterSpacing' => (float) $this->letter_spacing,
            'wordSpacing' => (float) $this->word_spacing,
            'colorBlindnessMode' => $this->color_blindness_mode,
        ];
    }
}
