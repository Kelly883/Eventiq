<?php

namespace App\Features\Ticketing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTiersRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tiers' => 'required|array',
            'tiers.*.id' => 'nullable|integer|exists:ticket_tiers,id',
            'tiers.*.event_id' => 'required|integer|exists:events,id',
            'tiers.*.name' => 'required|string|max:255',
            'tiers.*.description' => 'nullable|string|max:2000',
            'tiers.*.price' => 'required|numeric|min:0',
            'tiers.*.quantity' => 'nullable|integer|min:1',
            // For NEW tiers (no id), prevent sales_start_date in the past
            // For existing tiers, we allow past dates since they may have already started selling
            'tiers.*.sales_start_date' => 'nullable|date|prohibited_if:is_new_tier,1',
            'tiers.*.sales_end_date' => 'nullable|date|after:sales_start_date',
            'tiers.*.benefits_description' => 'nullable|string|max:2000',
            'tiers.*.tier_image_url' => 'nullable|url|max:500',
            'tiers.*.max_per_customer' => 'nullable|integer|min:1',
            'tiers.*.min_purchase' => 'nullable|integer|min:1',
            'tiers.*.max_purchase' => 'nullable|integer|min:1',
            'tiers.*.early_bird_price' => 'nullable|numeric|min:0|lt:price',
            'tiers.*.early_bird_end_date' => 'nullable|date|before:sales_end_date',
            'tiers.*.benefits' => 'nullable|array',
            'tiers.*.is_active' => 'boolean',
            
            // New pre-launch fields
            'tiers.*.tier_order' => 'nullable|integer|min:0',
            'tiers.*.status' => 'nullable|string|in:draft,published,archived',
            'tiers.*.currency' => 'nullable|string|size:3',
            'tiers.*.voucher_code' => 'nullable|string|max:50|alpha_num',
            'tiers.*.sales_channel' => 'nullable|string|max:50',
            'tiers.*.published_at' => 'nullable|date',
        ];
    }

    /**
     * Configure the validator instance with custom after-validation hooks.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tiers = $this->input('tiers', []);
            
            foreach ($tiers as $index => $tier) {
                // If this is a NEW tier (no id provided), validate sales_start_date is not in the past
                if (empty($tier['id']) && !empty($tier['sales_start_date'])) {
                    $startDate = \Carbon\Carbon::parse($tier['sales_start_date']);
                    if ($startDate->isPast()) {
                        $validator->errors()->add(
                            "tiers.{$index}.sales_start_date",
                            'For new tiers, the sales start date cannot be in the past.'
                        );
                    }
                }
                
                // Validate quantity constraints if both quantity and sold_count are provided for existing tiers
                if (!empty($tier['id']) && isset($tier['quantity']) && isset($tier['sold_count'])) {
                    if ((int) $tier['sold_count'] > (int) $tier['quantity']) {
                        $validator->errors()->add(
                            "tiers.{$index}.sold_count",
                            'Sold count cannot exceed the total quantity.'
                        );
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
            'tiers.*.sales_end_date.after' => 'Sales end date must be after sales start date.',
            'tiers.*.early_bird_price.lt' => 'Early bird price must be less than regular price.',
            'tiers.*.max_purchase.min' => 'Max purchase must be at least 1.',
            'tiers.*.quantity.min' => 'Quantity must be at least 1.',
            'tiers.*.min_purchase.min' => 'Min purchase must be at least 1.',
            'tiers.*.max_per_customer.min' => 'Max per customer must be at least 1.',
            'tiers.*.status.in' => 'Status must be one of: draft, published, or archived.',
            'tiers.*.currency.size' => 'Currency must be a 3-character ISO code (e.g., USD, EUR, GBP).',
        ];
    }
}