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
        ];
    }
}

