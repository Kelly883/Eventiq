<?php

namespace App\Features\PushNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePushTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:100'],
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:1000'],
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
