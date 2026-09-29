<?php

namespace App\Features\ApiKeys\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organizer !== null;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'subscribedEvents' => ['required', 'array', 'min:1'],
            'subscribedEvents.*' => [
                'string',
                'in:order.created,order.updated,ticket.issued,ticket.checked_in,payment.succeeded,payment.failed,refund.processed,event.created,event.updated',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subscribedEvents.required' => 'At least one subscribed event is required.',
            'subscribedEvents.*.in' => 'The event type :input is not supported.',
        ];
    }
}
