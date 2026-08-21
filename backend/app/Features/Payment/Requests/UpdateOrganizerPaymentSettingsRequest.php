<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizerPaymentSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'paystack_subaccount_code' => ['nullable', 'string', 'max:255'],
            'paystack_business_name' => ['nullable', 'string', 'max:255'],
            'paystack_recipient_code' => ['nullable', 'string', 'max:255'],
            'paystack_connect_status' => ['nullable', 'string', 'in:enabled,pending,not_connected,disabled'],
            'paystack_connected_at' => ['nullable', 'date'],
            'flutterwave_subaccount_id' => ['nullable', 'string', 'max:255'],
            'flutterwave_business_reference' => ['nullable', 'string', 'max:255'],
            'flutterwave_connect_status' => ['nullable', 'string', 'in:enabled,pending,not_connected,disabled'],
            'flutterwave_connected_at' => ['nullable', 'date'],
        ];
    }
}
