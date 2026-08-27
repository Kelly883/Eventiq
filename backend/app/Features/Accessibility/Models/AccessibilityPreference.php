<?php
namespace App\Features\Accessibility\Models;
use Illuminate\Database\Eloquent\Model;

class AccessibilityPreference extends Model
{
    protected $table = 'accessibility_preferences';
    protected $fillable = [
        'userId','fontSize','highContrast','screenReaderOptimized',
        'focusIndicatorEnhanced','motionReduced','lineHeight',
        'letterSpacing','wordSpacing','colorBlindnessMode'
    ];
    protected $casts = [
        'highContrast'=>'boolean','screenReaderOptimized'=>'boolean',
        'focusIndicatorEnhanced'=>'boolean','motionReduced'=>'boolean',
        'fontSize'=>'integer','lineHeight'=>'decimal:2',
        'letterSpacing'=>'decimal:3','wordSpacing'=>'decimal:3'
    ];
    public $timestamps = true;
}
