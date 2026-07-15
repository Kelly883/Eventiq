<?php

namespace App\Features\Fraud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FraudCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'email' => ['nullable', 'email'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'reference' => ['required', 'string'],
            'provider' => ['required', 'in:paystack,flutterwave'],
            'ip' => ['nullable', 'ip'],
            'session_id' => ['nullable', 'string'],
        ];
    }
}
