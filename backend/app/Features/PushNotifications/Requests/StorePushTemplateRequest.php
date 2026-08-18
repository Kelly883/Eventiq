<?php

namespace App\Features\PushNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePushTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:10'],
            'badge' => ['nullable', 'integer', 'min:0'],
            'sound' => ['nullable', 'string', 'max:100'],
            'click_action' => ['nullable', 'string', 'max:255'],
            'collapse_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
