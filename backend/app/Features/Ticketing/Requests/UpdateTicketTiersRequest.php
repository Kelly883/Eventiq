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
        $tiers = $this->input('ticketTiers', $this->input('tiers', null));
        if ($tiers !== null && !$this->has('ticketTiers')) {
            $this->merge(['ticketTiers' => $tiers]);
        }
        $tiers2 = $this->input('ticket_tiers');
        if ($tiers2 !== null && !$this->has('ticketTiers')) {
            $this->merge(['ticketTiers' => $tiers2]);
        }
    }

    public function rules(): array
    {
        $eventId = $this->route('event') ?? $this->route('eventId') ?? $this->input('eventId') ?? $this->input('event_id');
        return [
            'eventId' => ['sometimes', 'integer', 'exists:events,id'],
            'event_id' => ['sometimes', 'integer', 'exists:events,id'],
            'version' => ['sometimes', 'integer'],
            'ticketTiers' => ['present', 'array', 'max:10'],
            'ticketTiers.*.id' => ['nullable', 'integer', Rule::exists('ticket_tiers', 'id')->where(function ($query) use ($eventId) {
                if ($eventId) $query->where('event_id', $eventId);
            })],
            'ticketTiers.*.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'ticketTiers.*.price' => ['sometimes', 'numeric', 'gt:0'],
            'ticketTiers.*.quantity' => ['sometimes', 'integer', 'gt:0'],
            'ticketTiers.*.sales_start_date' => ['nullable', 'date'],
            'ticketTiers.*.sales_end_date' => ['nullable', 'date'],
            'ticketTiers.*.benefits_description' => ['nullable', 'string', 'max:2000'],
            'ticketTiers.*.tier_image_url' => ['nullable', 'string', 'max:2048'],
            'ticketTiers.*.early_bird_price' => ['nullable', 'numeric', 'gt:0'],
            'ticketTiers.*.early_bird_end_date' => ['nullable', 'date'],
            'ticketTiers.*.max_per_customer' => ['nullable', 'integer', 'gt:0'],
            'ticketTiers.*.currency' => ['sometimes', 'string', 'max:10'],
            'ticketTiers.*.status' => ['sometimes', 'string', 'in:published,draft,archived'],
            'ticketTiers.*.is_active' => ['sometimes', 'boolean'],
            'ticketTiers.*.tier_order' => ['sometimes', 'integer', 'min:0'],
            'tiers' => ['sometimes', 'array'],
            'tiers.*.name' => ['sometimes', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $tiers = $this->input('ticketTiers', $this->input('tiers', null));
            if (!is_array($tiers)) {
                return;
            }

            // Application-level duplicate name detection — SQLite considers NULL
            // distinct in unique indexes so a DB constraint alone is unreliable.
            $seenNames = [];
            foreach ($tiers as $index => $tier) {
                $name = $tier['name'] ?? null;
                if ($name !== null && trim((string) $name) !== '') {
                    $normalized = strtolower(trim((string) $name));
                    if (in_array($normalized, $seenNames, true)) {
                        $v->errors()->add("ticketTiers.{$index}.name", 'A tier with this name already exists for this event.');
                    }
                    $seenNames[] = $normalized;
                }
            }

            foreach ($tiers as $index => $tier) {
                $id = $tier['id'] ?? null;
                $isNew = empty($id);

                $name = $tier['name'] ?? null;
                $price = $tier['price'] ?? null;
                $quantity = $tier['quantity'] ?? null;
                $earlyBirdPrice = $tier['early_bird_price'] ?? $tier['earlyBirdPrice'] ?? null;
                $salesStart = $tier['sales_start_date'] ?? $tier['salesStartDate'] ?? null;
                $salesEnd = $tier['sales_end_date'] ?? $tier['salesEndDate'] ?? null;
                $earlyBirdEnd = $tier['early_bird_end_date'] ?? $tier['earlyBirdEndDate'] ?? null;

                if ($isNew) {
                    if ($name === null || trim((string) $name) === '') {
                        $v->errors()->add("ticketTiers.{$index}.name", 'The name field is required.');
                    }
                    if ($price === null || $price === '') {
                        $v->errors()->add("ticketTiers.{$index}.price", 'The price field is required.');
                    }
                    if ($quantity === null || $quantity === '') {
                        $v->errors()->add("ticketTiers.{$index}.quantity", 'The quantity field is required.');
                    }
                }

                if ($name !== null && !$isNew && trim((string) $name) === '') {
                    $v->errors()->add("ticketTiers.{$index}.name", 'The name field cannot be empty.');
                }

                if ($earlyBirdPrice !== null && $price !== null && $price !== '') {
                    if ((float) $earlyBirdPrice >= (float) $price) {
                        $v->errors()->add("ticketTiers.{$index}.early_bird_price", 'Early bird price must be less than the regular price.');
                    }
                }

                if ($earlyBirdPrice !== null && $earlyBirdPrice !== '' && ($earlyBirdEnd === null || $earlyBirdEnd === '')) {
                    $v->errors()->add("ticketTiers.{$index}.early_bird_end_date", 'The early bird end date field is required when early bird price is present.');
                }

                if ($salesStart && $salesEnd) {
                    try {
                        $start = \Carbon\Carbon::parse($salesStart);
                        $end = \Carbon\Carbon::parse($salesEnd);
                        if ($end->lte($start)) {
                            $v->errors()->add("ticketTiers.{$index}.sales_end_date", 'Sales end date must be after sales start date.');
                        }
                    } catch (\Throwable $e) {}
                }

                if ($earlyBirdEnd && $salesEnd) {
                    try {
                        $earlyEnd = \Carbon\Carbon::parse($earlyBirdEnd);
                        $salesEndDate = \Carbon\Carbon::parse($salesEnd);
                        if ($earlyEnd->gte($salesEndDate)) {
                            $v->errors()->add("ticketTiers.{$index}.early_bird_end_date", 'Early bird end date must be before sales end date.');
                        }
                    } catch (\Throwable $e) {}
                }

                $tierImageUrl = $tier['tier_image_url'] ?? $tier['tierImageUrl'] ?? null;
                if ($tierImageUrl) {
                    if (str_starts_with($tierImageUrl, 'data:') || str_starts_with($tierImageUrl, 'blob:')) {
                        // ok
                    } elseif (str_contains($tierImageUrl, '@')) {
                        $v->errors()->add("ticketTiers.{$index}.tier_image_url", 'Tier image URL must not contain userinfo.');
                    } elseif (filter_var($tierImageUrl, FILTER_VALIDATE_URL) === false) {
                        $v->errors()->add("ticketTiers.{$index}.tier_image_url", 'Tier image URL must be a valid URL.');
                    } else {
                        $allowedHosts = array_filter(array_map('trim', explode(',', config('ticketing.tier_image_allowed_hosts', ''))));
                        if (empty($allowedHosts)) {
                            $allowedHosts = array_filter([
                                parse_url(config('app.url'), PHP_URL_HOST),
                                parse_url(config('filesystems.disks.s3.url') ?? '', PHP_URL_HOST),
                                parse_url(config('filesystems.disks.s3.endpoint') ?? '', PHP_URL_HOST),
                                parse_url(env('AWS_URL', ''), PHP_URL_HOST),
                                'localhost',
                                '127.0.0.1',
                            ]);
                        }
                        $host = parse_url($tierImageUrl, PHP_URL_HOST);
                        if (!$host || !in_array($host, $allowedHosts, true)) {
                            $v->errors()->add("ticketTiers.{$index}.tier_image_url", 'Tier image URL must be from our storage.');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ticketTiers.present' => 'The ticket tiers field is required.',
            'ticketTiers.required' => 'The ticket tiers field is required.',
            'ticketTiers.array' => 'The ticket tiers field is required.',
            'ticketTiers.max' => 'The ticket tiers may not have more than 10 items.',
            'ticketTiers.*.price.gt' => 'Price must be greater than 0.',
            'ticketTiers.*.quantity.gt' => 'Quantity must be greater than 0.',
            'ticketTiers.*.sales_end_date.after' => 'Sales end date must be after sales start date.',
            'ticketTiers.*.early_bird_price.lt' => 'Early bird price must be less than the regular price.',
            'ticketTiers.*.tier_image_url.url' => 'Tier image URL must be a valid URL.',
            'ticketTiers.*.tier_image_url.string' => 'Tier image URL must be a valid URL.',
            'ticketTiers.*.early_bird_end_date.required_with' => 'The early bird end date field is required when early bird price is present.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        parent::failedValidation($validator);
    }
}
