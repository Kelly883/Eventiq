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
            'token' => ['required', 'string'],
            'provider' => ['required', 'string', 'in:fcm'],
            'device_type' => ['required', 'string', 'in:web,ios,android'],
            'previous_token' => ['nullable', 'string'],
        ];
    }
}
