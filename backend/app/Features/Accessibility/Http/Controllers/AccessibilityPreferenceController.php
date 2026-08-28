<?php

namespace App\Features\Accessibility\Http\Controllers;

use App\Features\Accessibility\Models\AccessibilityPreference;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccessibilityPreferenceController extends Controller
{
    /**
     * Default values returned when the user has no stored preferences.
     *
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'fontSize' => 16,
        'highContrast' => false,
        'screenReaderOptimized' => false,
        'focusIndicatorEnhanced' => false,
        'motionReduced' => false,
        'lineHeight' => 1.50,
        'letterSpacing' => 0,
        'wordSpacing' => 0,
        'colorBlindnessMode' => 'none',
    ];

    /**
     * Display the user's accessibility preferences.
     */
    public function show(): JsonResponse
    {
        $pref = AccessibilityPreference::where('userId', Auth::id())->first();

        if (! $pref) {
            return response()->json(
                array_merge(self::DEFAULTS, ['message' => 'Defaults returned']),
                200
            );
        }

        return response()->json(
            array_merge($pref->toArray(), ['message' => 'Preferences loaded']),
            200
        );
    }

    /**
     * Update the user's accessibility preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fontSize' => 'sometimes|integer|min:12|max:24',
            'highContrast' => 'sometimes|boolean',
            'screenReaderOptimized' => 'sometimes|boolean',
            'focusIndicatorEnhanced' => 'sometimes|boolean',
            'motionReduced' => 'sometimes|boolean',
            'lineHeight' => 'sometimes|numeric|min:1|max:2',
            'letterSpacing' => 'sometimes|numeric|min:0|max:0.2',
            'wordSpacing' => 'sometimes|numeric|min:0|max:0.2',
            'colorBlindnessMode' => 'sometimes|in:none,protanopia,deuteranopia,tritanopia',
        ]);

        $pref = AccessibilityPreference::firstOrNew(
            ['userId' => Auth::id()],
            self::DEFAULTS
        );

        $pref->fill($validated);
        $pref->save();

        return response()->json(
            array_merge($pref->toArray(), ['message' => 'Accessibility preferences updated']),
            200
        );
    }
}