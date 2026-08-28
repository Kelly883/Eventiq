<?php

namespace App\Features\Localization\Http\Controllers;

use App\Models\LanguagePreference;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LanguagePreferenceController extends Controller
{
    /**
     * Display the user's language and locale preferences.
     */
    public function show(): JsonResponse
    {
        $pref = LanguagePreference::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'language' => 'en',
                'region' => 'US',
                'date_format' => 'MM/DD/YYYY',
                'time_format' => '12-hour',
                'currency' => 'USD',
                'number_format' => 'period',
                'rtl_enabled' => false,
            ]
        );

        return response()->json(
            array_merge($pref->toArray(), ['message' => 'Preferences loaded']),
            200
        );
    }

    /**
     * Update the user's language and locale preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $key = 'language-update:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'language' => 'sometimes|string|size:2|in:en,es,fr,de,it,pt,ru,ar,he,ur,zh,ja,ko,hi,bn,tr,pl,nl,sv,da,fi,no,cs,el,th,vi,id,ms,fil',
            'region' => 'sometimes|string|size:2',
            'dateFormat' => 'sometimes|in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD',
            'timeFormat' => 'sometimes|in:12-hour,24-hour',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,JPY,CAD,AUD,CHF,CNY,INR,MXN,BRL,RUB,KRW,SEK,NOK,DKK,PLN,CZK,HUF,TRY,ZAR,SGD,HKD,NZD',
            'numberFormat' => 'sometimes|in:comma,period',
            'rtlEnabled' => 'sometimes|boolean',
        ]);

        $pref = LanguagePreference::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'language' => 'en',
                'region' => 'US',
                'date_format' => 'MM/DD/YYYY',
                'time_format' => '12-hour',
                'currency' => 'USD',
                'number_format' => 'period',
                'rtl_enabled' => false,
            ]
        );

        $pref->fill([
            'language' => $validated['language'] ?? $pref->language,
            'region' => $validated['region'] ?? $pref->region,
            'date_format' => $validated['dateFormat'] ?? $pref->date_format,
            'time_format' => $validated['timeFormat'] ?? $pref->time_format,
            'currency' => $validated['currency'] ?? $pref->currency,
            'number_format' => $validated['numberFormat'] ?? $pref->number_format,
            'rtl_enabled' => $validated['rtlEnabled'] ?? $pref->rtl_enabled,
        ]);

        $pref->save();

        return response()->json(
            array_merge($pref->toArray(), ['message' => 'Language preferences updated']),
            200
        );
    }
}