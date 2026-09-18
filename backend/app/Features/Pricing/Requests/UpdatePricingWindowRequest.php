<?php

namespace App\Features\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricingWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('eventId') ?? $this->route('event_id') ?? $this->route('event');

        return [
            'window_name' => 'sometimes|required|string|max:100',
            'ticket_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('ticket_tiers', 'id')->where(function ($q) use ($eventId) {
                    $q->where('event_id', $eventId);
                }),
            ],
            'start_date_time' => 'sometimes|required|date',
            'end_date_time' => 'sometimes|required|date|after:start_date_time',
            'price' => 'sometimes|required|numeric|min:0|max:99999999.99',
            'quantity_limit' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_category_id.exists' => 'The selected ticket tier does not exist or does not belong to this event.',
            'end_date_time.after' => 'The window end date must be after the start date.',
        ];
    }
}

