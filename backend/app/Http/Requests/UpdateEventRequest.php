<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('venueName') && !$this->has('venue_name')) {
            $merge['venue_name'] = $this->input('venueName');
        }
        if ($this->has('venueAddress') && !$this->has('venue_address')) {
            $merge['venue_address'] = $this->input('venueAddress');
        }
        // banner_image_url only via upload-banner, ignore direct payload
        // Combine split dates if present
        if (!$this->has('start_datetime') && $this->has('startDate')) {
            $date = $this->input('startDate');
            $time = $this->input('startTime') ?? null;
            if ($date) {
                if ($time && preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    $time .= ':00';
                }
                $merge['start_datetime'] = $time ? trim($date . ' ' . $time) : $date;
            }
        } elseif (!$this->has('start_datetime') && $this->has('start_date')) {
            $merge['start_datetime'] = $this->input('start_date');
        }
        if (!$this->has('end_datetime') && $this->has('endDate')) {
            $date = $this->input('endDate');
            $time = $this->input('endTime') ?? null;
            if ($date) {
                if ($time && preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    $time .= ':00';
                }
                $merge['end_datetime'] = $time ? trim($date . ' ' . $time) : $date;
            }
        } elseif (!$this->has('end_datetime') && $this->has('end_date')) {
            $merge['end_datetime'] = $this->input('end_date');
        }
        if ($this->has('ticketTiers') && !$this->has('ticket_tiers')) {
            $merge['ticket_tiers'] = $this->input('ticketTiers');
        }
        if ($this->has('capacity') && is_string($this->input('capacity')) && $this->input('capacity') !== '') {
            $merge['capacity'] = (int) $this->input('capacity');
        }
        if ($this->has('isPublic') && !$this->has('is_public')) {
            $merge['is_public'] = filter_var($this->input('isPublic'), FILTER_VALIDATE_BOOLEAN);
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $eventId = $this->route('event') ?? $this->route('eventId') ?? $this->route('id');
        // Handle route model binding where param may be model instance
        if ($eventId instanceof \App\Models\Event) {
            $eventId = $eventId->id;
        }

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'start_datetime' => ['sometimes', 'date'],
            'end_datetime' => ['sometimes', 'date', 'after_or_equal:start_datetime'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:draft,published'],
            'is_public' => ['nullable', 'boolean'],
            'isPublic' => ['nullable', 'boolean'],
            'ticket_tiers' => ['nullable', 'array', 'max:10'],
            'ticket_tiers.*.id' => ['nullable', 'integer', Rule::exists('ticket_tiers', 'id')->where(function ($query) use ($eventId) {
                if ($eventId) {
                    $query->where('event_id', $eventId);
                }
            })],
            'ticket_tiers.*.name' => ['required_with:ticket_tiers', 'string', 'max:100'],
            'ticket_tiers.*.price' => ['required_with:ticket_tiers', 'numeric', 'min:0'],
            'ticket_tiers.*.quantity' => ['nullable', 'integer', 'min:0'],
            'ticket_tiers.*.sales_start_date' => ['nullable', 'date'],
            'ticket_tiers.*.sales_end_date' => ['nullable', 'date'],
            'ticket_tiers.*.salesStartDate' => ['nullable', 'date'],
            'ticket_tiers.*.salesEndDate' => ['nullable', 'date'],
            'ticket_tiers.*.early_bird_price' => ['nullable', 'numeric', 'min:0'],
            'ticket_tiers.*.earlyBirdPrice' => ['nullable', 'numeric', 'min:0'],
            'ticket_tiers.*.early_bird_end_date' => ['nullable', 'date'],
            'ticket_tiers.*.earlyBirdEndDate' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tiers = $this->input('ticket_tiers');
            if (!is_array($tiers)) {
                return;
            }

            foreach ($tiers as $index => $tier) {
                $earlyBirdPrice = $tier['early_bird_price'] ?? $tier['earlyBirdPrice'] ?? null;
                $price = $tier['price'] ?? null;
                $salesStart = $tier['sales_start_date'] ?? $tier['salesStartDate'] ?? null;
                $salesEnd = $tier['sales_end_date'] ?? $tier['salesEndDate'] ?? null;
                $earlyBirdEnd = $tier['early_bird_end_date'] ?? $tier['earlyBirdEndDate'] ?? null;

                // Require early_bird_end_date when early_bird_price present
                if ($earlyBirdPrice !== null && $earlyBirdPrice !== '' && ($earlyBirdEnd === null || $earlyBirdEnd === '')) {
                    $validator->errors()->add(
                        "ticket_tiers.{$index}.early_bird_end_date",
                        'The early bird end date field is required when early bird price is present.'
                    );
                }

                // early_bird_price must be less than price when both present
                if ($earlyBirdPrice !== null && $earlyBirdPrice !== '' && $price !== null && $price !== '') {
                    if ((float) $earlyBirdPrice >= (float) $price) {
                        $validator->errors()->add(
                            "ticket_tiers.{$index}.early_bird_price",
                            'Early bird price must be less than regular price.'
                        );
                    }
                }

                if ($salesStart && $salesEnd) {
                    try {
                        $start = \Carbon\Carbon::parse($salesStart);
                        $end = \Carbon\Carbon::parse($salesEnd);
                        if ($end->lte($start)) {
                            $validator->errors()->add(
                                "ticket_tiers.{$index}.sales_end_date",
                                'Sales end date must be after sales start date.'
                            );
                        }
                    } catch (\Throwable $e) {}
                }

                if ($earlyBirdEnd && $salesEnd) {
                    try {
                        $earlyEnd = \Carbon\Carbon::parse($earlyBirdEnd);
                        $salesEndDate = \Carbon\Carbon::parse($salesEnd);
                        if ($earlyEnd->gte($salesEndDate)) {
                            $validator->errors()->add(
                                "ticket_tiers.{$index}.early_bird_end_date",
                                'Early bird end date must be before sales end date.'
                            );
                        }
                    } catch (\Throwable $e) {}
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ticket_tiers.*.early_bird_price.lt' => 'Early bird price must be less than regular price.',
            'ticket_tiers.*.earlyBirdPrice.lt' => 'Early bird price must be less than regular price.',
            'ticket_tiers.*.sales_end_date.after' => 'Sales end date must be after sales start date.',
            'ticket_tiers.*.salesEndDate.after' => 'Sales end date must be after sales start date.',
            'ticket_tiers.*.early_bird_end_date.after' => 'Early bird end date must be after sales start date.',
            'ticket_tiers.*.earlyBirdEndDate.after' => 'Early bird end date must be after sales start date.',
            'ticket_tiers.*.early_bird_end_date.before' => 'Early bird end date must be before sales end date.',
            'ticket_tiers.*.earlyBirdEndDate.before' => 'Early bird end date must be before sales end date.',
            'ticket_tiers.max' => 'Maximum 10 ticket tiers per event.',
        ];
    }
}
