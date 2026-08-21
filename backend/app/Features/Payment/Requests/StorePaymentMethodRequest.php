<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string', 'in:paystack,flutterwave'],
            'type' => ['required', 'string', 'in:card,bank_transfer,ussd,qr,mobile_money'],
            'gateway_payment_method_id' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:50'],
            'last_four' => ['nullable', 'string', 'size:4'],
            'exp_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'exp_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'account_number_last4' => ['nullable', 'string', 'size:4'],
            'expires_at' => ['nullable', 'date'],
            'is_default' => ['sometimes', 'boolean'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_country' => ['nullable', 'string', 'size:2'],
            'billing_zip' => ['nullable', 'string', 'max:20'],
        ];
    }
}
