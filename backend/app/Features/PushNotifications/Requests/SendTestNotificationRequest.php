<?php

namespace App\Features\PushNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTestNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:255'],
        ];
    }
}
