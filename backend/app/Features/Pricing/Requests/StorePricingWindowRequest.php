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
            'start_date_time' => 'required|date',
            'end_date_time' => 'required|date|after:start_date_time',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'quantity_limit' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ];

        // Overlap detection: ensure no active window with overlapping date range
        // for the same event + ticket category (only if the new window is active)
        if ($this->boolean('is_active') !== false) {
            $rules['start_date_time'][] = function ($attribute, $value, $fail) use ($eventId) {
                $endDate = $this->input('end_date_time');
                $catId = $this->input('ticket_category_id');

                $overlap = \App\Features\Pricing\Models\PricingWindow::where('event_id', $eventId)
                    ->where('ticket_category_id', $catId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($value, $endDate) {
                        $q->whereBetween('start_date_time', [$value, $endDate])
                          ->orWhereBetween('end_date_time', [$value, $endDate])
                          ->orWhere(function ($q) use ($value, $endDate) {
                              $q->where('start_date_time', '<=', $value)
                                ->where('end_date_time', '>=', $endDate);
                          });
                    })
                    ->exists();

                if ($overlap) {
                    $fail('An active pricing window already exists for this ticket category with overlapping dates.');
                }
            };
        }

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

