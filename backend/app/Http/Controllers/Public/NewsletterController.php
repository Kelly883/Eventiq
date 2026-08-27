<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Anonymous newsletter subscription. There was previously no backend at all:
 * the homepage NewsletterSection flipped local UI state and claimed "Thanks!
 * We'll keep you updated." with zero persistence. This controller gives the
 * form a real, idempotent, throttled target so a success message means the
 * email was actually recorded.
 */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first('email'),
                'message' => 'A valid email address is required.',
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));

        // Upsert idempotently: re-subscribing the same address is not an error.
        NewsletterSubscriber::updateOrCreate(['email' => $email], ['email' => $email]);

        return response()->json(['message' => 'Subscribed successfully.'], 201);
    }
}