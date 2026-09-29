<?php

namespace App\Features\Accessibility\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccessibilityPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AccessibilityPreferenceController extends Controller
{
    private const DEFAULTS = [
        'fontSize' => 16,
        'highContrast' => false,
        'screenReaderOptimized' => false,
        'focusIndicatorEnhanced' => false,
        'motionReduced' => false,
        'lineHeight' => 1.5,
        'letterSpacing' => 0.0,
        'wordSpacing' => 0.0,
        'colorBlindnessMode' => 'none',
    ];

    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $pref = AccessibilityPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'font_size' => 16,
                'high_contrast' => false,
                'screen_reader_optimized' => false,
                'focus_indicator_enhanced' => false,
                'motion_reduced' => false,
                'line_height' => 1.5,
                'letter_spacing' => 0.0,
                'word_spacing' => 0.0,
                'color_blindness_mode' => 'none',
            ]
        );

        return response()->json(
            array_merge($pref->toArray(), ['message' => 'Preferences loaded']),
            200
        );
    }

    public function update(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $validated = $request->validate([
                'fontSize' => 'sometimes|integer|min:12|max:24',
                'highContrast' => 'sometimes|boolean',
                'screenReaderOptimized' => 'sometimes|boolean',
                'focusIndicatorEnhanced' => 'sometimes|boolean',
                'motionReduced' => 'sometimes|boolean',
 'lineHeight' => 'sometimes|numeric|between:1.0,2.0',
                'letterSpacing' => 'sometimes|numeric|min:0|max:0.2',
                'wordSpacing' => 'sometimes|numeric|min:0|max:0.2',
                'colorBlindnessMode' => 'sometimes|in:none,protanopia,deuteranopia,tritanopia',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 400);
        }

        $pref = AccessibilityPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'font_size' => 16,
                'high_contrast' => false,
                'screen_reader_optimized' => false,
                'focus_indicator_enhanced' => false,
                'motion_reduced' => false,
                'line_height' => 1.5,
                'letter_spacing' => 0.0,
                'word_spacing' => 0.0,
                'color_blindness_mode' => 'none',
            ]
        );

        $update = [];
        if (array_key_exists('fontSize', $validated)) $update['font_size'] = $validated['fontSize'];
        if (array_key_exists('highContrast', $validated)) $update['high_contrast'] = $validated['highContrast'];
        if (array_key_exists('screenReaderOptimized', $validated)) $update['screen_reader_optimized'] = $validated['screenReaderOptimized'];
        if (array_key_exists('focusIndicatorEnhanced', $validated)) $update['focus_indicator_enhanced'] = $validated['focusIndicatorEnhanced'];
        if (array_key_exists('motionReduced', $validated)) $update['motion_reduced'] = $validated['motionReduced'];
        if (array_key_exists('lineHeight', $validated)) $update['line_height'] = $validated['lineHeight'];
        if (array_key_exists('letterSpacing', $validated)) $update['letter_spacing'] = $validated['letterSpacing'];
        if (array_key_exists('wordSpacing', $validated)) $update['word_spacing'] = $validated['wordSpacing'];
        if (array_key_exists('colorBlindnessMode', $validated)) $update['color_blindness_mode'] = $validated['colorBlindnessMode'];

        $pref->update($update);

        \App\Services\Audit\AuditLogger::log(
            'update',
            $request->user(),
            'accessibility_preference',
            $pref->id,
            'Updated accessibility preferences',
            [],
            $update,
            $request
        );

        return response()->json(
            array_merge($pref->fresh()->toArray(), ['message' => 'Accessibility preferences updated']),
            200
        );
    }
}
