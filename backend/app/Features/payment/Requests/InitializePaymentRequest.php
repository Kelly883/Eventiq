<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitializePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string'],

            // Common fields for gateway initialization
            'email' => ['required', 'string'],
            'reference' => ['sometimes', 'nullable', 'string'],
            'callback_url' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],

            // Optional customer info
            'name' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string'],
            'customizations' => ['sometimes', 'array'],
        ];

    }
}

