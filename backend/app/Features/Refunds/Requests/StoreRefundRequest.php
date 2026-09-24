<?php

namespace App\Features\Refunds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'string', 'exists:tickets,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'refund_method' => ['required', 'string', 'in:original_payment,store_credit,bank_transfer'],
            'explanation' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
