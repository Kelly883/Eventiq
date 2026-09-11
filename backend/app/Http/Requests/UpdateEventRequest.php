<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            $merge['is_public'] = $this->boolean('isPublic');
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
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
            'ticket_tiers.*.id' => ['nullable', 'integer', 'exists:ticket_tiers,id'],
            'ticket_tiers.*.name' => ['required_with:ticket_tiers', 'string', 'max:100'],
            'ticket_tiers.*.price' => ['required_with:ticket_tiers', 'numeric', 'min:0'],
            'ticket_tiers.*.quantity' => ['nullable', 'integer', 'min:0'],
            'ticket_tiers.*.sales_start_date' => ['nullable', 'date'],
            'ticket_tiers.*.sales_end_date' => ['nullable', 'date'],
            'ticket_tiers.*.salesStartDate' => ['nullable', 'date'],
            'ticket_tiers.*.salesEndDate' => ['nullable', 'date'],
        ];
    }
}
