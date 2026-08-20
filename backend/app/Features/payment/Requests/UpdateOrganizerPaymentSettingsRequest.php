<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizerPaymentSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string'],
            'payout_account' => ['nullable', 'string'],
        ];
    }
}

