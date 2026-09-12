<?php

namespace App\Features\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePricingWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('event_id') ?? $this->route('event');

        $rules = [
            'window_name' => 'required|string|max:100',
            'ticket_category_id' => [
                'required',
                'integer',
                'exists:ticket_tiers,id',
                Rule::exists('ticket_tiers', 'id')->where(function ($q) use ($eventId) {
                    $q->where('event_id', $eventId);
                }),
            ],
            'start_date_time' => ['required', 'date'],
            'end_date_time' => ['required', 'date', 'after:start_date_time'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'quantity_limit' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'ticket_category_id.required' => 'The ticket category (ticket tier) is required.',
            'ticket_category_id.exists' => 'The selected ticket tier does not exist or does not belong to this event.',
            'end_date_time.after' => 'The window end date must be after the start date.',
        ];
    }
}

