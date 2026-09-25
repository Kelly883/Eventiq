<?php

namespace App\Features\Refunds\Requests;

use App\Features\Refunds\Enums\RefundMethodEnum;
use App\Features\Refunds\Enums\RefundReasonEnum;
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
            'reason' => [
                'required',
                'string',
                'max:50',
                'in:' . implode(',', array_map(fn($c) => $c->value, RefundReasonEnum::cases())),
            ],
            'refund_method' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($c) => $c->value, RefundMethodEnum::cases())),
            ],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'idempotency_key' => ['nullable', 'string', 'max:255', 'unique:refund_requests,idempotency_key'],
        ];
    }
}