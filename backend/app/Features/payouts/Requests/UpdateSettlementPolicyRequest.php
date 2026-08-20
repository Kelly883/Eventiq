<?php

namespace App\Features\Payouts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettlementPolicyRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasRole('admin');
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'platform_fee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_frequency' => ['required', 'string', 'in:daily,weekly,biweekly,monthly,manual'],
            'minimum_payout_amount' => ['required', 'numeric', 'min:0'],
            'payment_methods' => ['sometimes', 'array'],
            'payment_methods.*' => ['string', 'in:bank_transfer,paypal,stripe,check'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
