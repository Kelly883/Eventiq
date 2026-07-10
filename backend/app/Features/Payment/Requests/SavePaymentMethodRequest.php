<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string'],
            'type' => ['required', 'string'],
            'provider_reference' => ['required', 'string'],
        ];
    }
}

