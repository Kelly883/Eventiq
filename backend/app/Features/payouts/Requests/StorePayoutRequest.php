<?php

namespace App\Features\Payouts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasRole('admin');
    }

    public function rules()
    {
        return [
            'organizer_id' => ['required', 'exists:organizers,id'],
            'event_id' => ['required', 'exists:events,id'],
            'settlement_policy_id' => ['required', 'exists:settlement_policies,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'payout_method' => ['required', 'string', 'in:bank_transfer,paypal,stripe,check'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'calculation' => ['sometimes', 'array'],
            'calculation.total_revenue' => ['required_with:calculation', 'numeric', 'min:0'],
            'calculation.platform_fee' => ['required_with:calculation', 'numeric', 'min:0'],
            'calculation.organizer_share' => ['required_with:calculation', 'numeric', 'min:0'],
            'calculation.tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'calculation.refund_amount' => ['sometimes', 'numeric', 'min:0'],
            'calculation.breakdown' => ['sometimes', 'array'],
        ];
    }
}
