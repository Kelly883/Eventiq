<?php

namespace App\Features\Ticketing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTiersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize camelCase to snake_case for ticketTiers
        $tiers = $this->input('ticketTiers', $this->input('tiers', null));
        if ($tiers !== null && !$this->has('ticketTiers')) {
            $this->merge(['ticketTiers' => $tiers]);
        }
        // Also handle ticket_tiers
        $tiers2 = $this->input('ticket_tiers');
        if ($tiers2 !== null && !$this->has('ticketTiers')) {
            $this->merge(['ticketTiers' => $tiers2]);
        }
        // Normalize isPublic etc. not needed here
    }

    public function rules(): array
    {
        $eventId = $this->route('event') ?? $this->route('eventId') ?? $this->input('eventId') ?? $this->input('event_id');
        return [
            // eventId is validated via route model binding and policy, but also check exists if provided
            'eventId' => ['sometimes', 'integer', 'exists:events,id'],
            'event_id' => ['sometimes', 'integer', 'exists:events,id'],
            'ticketTiers' => ['required', 'array', 'min:0', 'max:10'],
            'ticketTiers.*.id' => ['nullable', 'integer', Rule::exists('ticket_tiers', 'id')->where(function ($query) use ($eventId) {
                if ($eventId) $query->where('event_id', $eventId);
            })],
            'ticketTiers.*.name' => ['required', 'string', 'max:255'],
            'ticketTiers.*.price' => ['required', 'numeric', 'gt:0'],
            'ticketTiers.*.quantity' => ['required', 'integer', 'gt:0'],
            'ticketTiers.*.sales_start_date' => ['nullable', 'date'],
            'ticketTiers.*.sales_end_date' => ['nullable', 'date'],
            'ticketTiers.*.benefits_description' => ['nullable', 'string', 'max:2000'],
            'ticketTiers.*.tier_image_url' => ['nullable', 'url', 'max:2048'],
            'ticketTiers.*.early_bird_price' => ['nullable', 'numeric', 'gt:0'],
            'ticketTiers.*.early_bird_end_date' => ['nullable', 'date', 'required_with:ticketTiers.*.early_bird_price'],
            'ticketTiers.*.max_per_customer' => ['nullable', 'integer', 'gt:0'],
            // Compat for tiers key
            'tiers' => ['sometimes', 'array'],
            'tiers.*.name' => ['sometimes', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tiers = $this->input('ticketTiers', $this->input('tiers', []));
            if (!is_array($tiers)) return;

            foreach ($tiers as $index => $tier) {
                $price = $tier['price'] ?? null;
                $earlyBirdPrice = $tier['early_bird_price'] ?? $tier['earlyBirdPrice'] ?? null;
                $salesStart = $tier['sales_start_date'] ?? $tier['salesStartDate'] ?? null;
                $salesEnd = $tier['sales_end_date'] ?? $tier['salesEndDate'] ?? null;
                $earlyBirdEnd = $tier['early_bird_end_date'] ?? $tier['earlyBirdEndDate'] ?? null;

                // early_bird_price must be less than price
                if ($earlyBirdPrice !== null && $price !== null) {
                    if ((float) $earlyBirdPrice >= (float) $price) {
                        $validator->errors()->add(
                            "ticketTiers.{$index}.early_bird_price",
                            'Early bird price must be less than regular price.'
                        );
                    }
                }

                // sales_end_date must be after sales_start_date
                if ($salesStart && $salesEnd) {
                    try {
                        $start = \Carbon\Carbon::parse($salesStart);
                        $end = \Carbon\Carbon::parse($salesEnd);
                        if ($end->lte($start)) {
                            $validator->errors()->add(
                                "ticketTiers.{$index}.sales_end_date",
                                'Sales end date must be after sales start date.'
                            );
                        }
                    } catch (\Throwable $e) {}
                }

                // early_bird_end_date must be before sales_end_date if both present
                if ($earlyBirdEnd && $salesEnd) {
                    try {
                        $earlyEnd = \Carbon\Carbon::parse($earlyBirdEnd);
                        $salesEndDate = \Carbon\Carbon::parse($salesEnd);
                        if ($earlyEnd->gte($salesEndDate)) {
                            $validator->errors()->add(
                                "ticketTiers.{$index}.early_bird_end_date",
                                'Early bird end date must be before sales end date.'
                            );
                        }
                    } catch (\Throwable $e) {}
                }

                // tier_image_url allowlist — must be from our storage hosts if present
                $tierImageUrl = $tier['tier_image_url'] ?? $tier['tierImageUrl'] ?? null;
                if ($tierImageUrl) {
                    $allowedHosts = array_filter([
                        parse_url(config('app.url'), PHP_URL_HOST),
                        parse_url(config('filesystems.disks.s3.url') ?? '', PHP_URL_HOST),
                        parse_url(config('filesystems.disks.s3.endpoint') ?? '', PHP_URL_HOST),
                        parse_url(env('AWS_URL', ''), PHP_URL_HOST),
                    ]);
                    $host = parse_url($tierImageUrl, PHP_URL_HOST);
                    if (!empty($allowedHosts) && $host && !in_array($host, $allowedHosts, true)) {
                        // Allow data: and blob: for previews, but not external http
                        if (!str_starts_with($tierImageUrl, 'data:') && !str_starts_with($tierImageUrl, 'blob:')) {
                            $validator->errors()->add(
                                "ticketTiers.{$index}.tier_image_url",
                                'Tier image URL must be from our storage.'
                            );
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ticketTiers.required' => 'Ticket tiers are required.',
            'ticketTiers.*.name.required' => 'Tier name is required.',
            'ticketTiers.*.price.required' => 'Tier price is required.',
            'ticketTiers.*.price.gt' => 'Price must be greater than 0.',
            'ticketTiers.*.quantity.required' => 'Tier quantity is required.',
            'ticketTiers.*.quantity.gt' => 'Quantity must be greater than 0.',
            'ticketTiers.*.sales_end_date.after' => 'Sales end date must be after sales start date.',
            'ticketTiers.*.early_bird_price.lt' => 'Early bird price must be less than regular price.',
            'ticketTiers.*.tier_image_url.url' => 'Tier image URL must be a valid URL.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Spec says 400, but Laravel convention is 422. Return 422 with validation errors
        // to match existing exception handler for api/* (which returns 422).
        // If strict 400 required, uncomment next line:
        // $this->validator->setFallbackMessages([]);
        parent::failedValidation($validator);
    }
}
