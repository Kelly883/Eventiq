<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string'],
            'reference' => ['required', 'string'],
        ];
    }
}

