<?php

namespace App\Features\PushNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'in:web,android,ios'],
            // Sent when the frontend detects its token changed (rotation/
            // refresh) - lets the backend delete the stale row instead of
            // accumulating dead device tokens forever.
            'previous_token' => ['nullable', 'string'],
        ];
    }
}
