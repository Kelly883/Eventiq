<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize frontend camelCase / split date+time to spec snake_case
        $merge = [];

        if ($this->has('venueName') && !$this->has('venue_name')) {
            $merge['venue_name'] = $this->input('venueName');
        }
        if ($this->has('venueAddress') && !$this->has('venue_address')) {
            $merge['venue_address'] = $this->input('venueAddress');
        }
        // banner_image_url is only set via upload-banner endpoint, not direct payload
        // to prevent arbitrary external URL injection. Silently ignore bannerUrl/banner_image_url in create payload.
        // Frontend sends startDate/startTime/endDate/endTime; spec expects start_datetime/end_datetime
        if (!$this->has('start_datetime') && $this->has('startDate')) {
            $date = $this->input('startDate');
            $time = $this->input('startTime') ?? '00:00:00';
            // Normalize time to H:i:s
            if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                $time .= ':00';
            }
            $merge['start_datetime'] = trim($date . ' ' . $time);
        } elseif (!$this->has('start_datetime') && $this->has('start_date') && !$this->has('start_datetime')) {
            // Legacy start_date -> start_datetime
            $merge['start_datetime'] = $this->input('start_date');
        }
        if (!$this->has('end_datetime') && $this->has('endDate')) {
            $date = $this->input('endDate');
            $time = $this->input('endTime') ?? '23:59:59';
            if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                $time .= ':00';
            }
            // If endDate is empty (frontend fallback to startDate), use start
            if (empty($date) && $this->has('startDate')) {
                $date = $this->input('startDate');
            }
            if (!empty($date)) {
                $merge['end_datetime'] = trim($date . ' ' . $time);
            }
        } elseif (!$this->has('end_datetime') && $this->has('end_date')) {
            $merge['end_datetime'] = $this->input('end_date');
        }
        // Ticket tiers: frontend sends ticketTiers
        if ($this->has('ticketTiers') && !$this->has('ticket_tiers')) {
            $merge['ticket_tiers'] = $this->input('ticketTiers');
        }
        // Capacity may come as string
        if ($this->has('capacity') && is_string($this->input('capacity')) && $this->input('capacity') !== '') {
            $merge['capacity'] = (int) $this->input('capacity');
        }
        // isPublic vs status: frontend sends isPublic boolean, backend stores is_public
        if ($this->has('isPublic') && !$this->has('is_public')) {
            $merge['is_public'] = $this->boolean('isPublic');
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after_or_equal:start_datetime'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'capacity' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:draft,published'],
            'is_public' => ['nullable', 'boolean'],
            'isPublic' => ['nullable', 'boolean'],
            'ticket_tiers' => ['nullable', 'array', 'max:10'],
            'ticket_tiers.*.name' => ['required_with:ticket_tiers', 'string', 'max:100'],
            'ticket_tiers.*.price' => ['required_with:ticket_tiers', 'numeric', 'min:0'],
            'ticket_tiers.*.quantity' => ['nullable', 'integer', 'min:0'],
            'ticket_tiers.*.sales_start_date' => ['nullable', 'date'],
            'ticket_tiers.*.sales_end_date' => ['nullable', 'date'],
            // Also allow camelCase inside tiers for frontend compat
            'ticket_tiers.*.salesStartDate' => ['nullable', 'date'],
            'ticket_tiers.*.salesEndDate' => ['nullable', 'date'],
            'ticket_tiers.*.early_bird_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'ticket_tiers.*.earlyBirdPrice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'ticket_tiers.*.early_bird_end_date' => ['nullable', 'date', 'after:sales_start_date', 'before:sales_end_date'],
            'ticket_tiers.*.earlyBirdEndDate' => ['nullable', 'date', 'after:salesStartDate', 'before:salesEndDate'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required.',
            'start_datetime.required' => 'Start date and time are required.',
            'end_datetime.required' => 'End date and time are required.',
            'end_datetime.after' => 'End date must be after start date.',
            'capacity.required' => 'Capacity is required.',
            'status.in' => 'Status must be draft or published.',
            'ticket_tiers.max' => 'Maximum 10 ticket tiers per event.',
            'ticket_tiers.*.early_bird_price.lt' => 'Early bird price must be less than regular price.',
            'ticket_tiers.*.earlyBirdPrice.lt' => 'Early bird price must be less than regular price.',
            'ticket_tiers.*.sales_end_date.after' => 'Sales end date must be after sales start date.',
            'ticket_tiers.*.salesEndDate.after' => 'Sales end date must be after sales start date.',
            'ticket_tiers.*.early_bird_end_date.after' => 'Early bird end date must be after sales start date.',
            'ticket_tiers.*.earlyBirdEndDate.after' => 'Early bird end date must be after sales start date.',
            'ticket_tiers.*.early_bird_end_date.before' => 'Early bird end date must be before sales end date.',
            'ticket_tiers.*.earlyBirdEndDate.before' => 'Early bird end date must be before sales end date.',
        ];
    }
}
