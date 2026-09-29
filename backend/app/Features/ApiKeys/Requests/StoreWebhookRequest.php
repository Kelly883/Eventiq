<?php

namespace App\Features\ApiKeys\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organizer !== null;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->passesSsrFCheck($value)) {
                    $fail('The webhook URL must not point to a private or internal address.');
                }
            }],
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

    private function passesSsrFCheck(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! $this->isPrivateIp($host);
        }

        try {
            $resolved = gethostbyname($host);

            return $resolved !== $host && ! $this->isPrivateIp($resolved);
        } catch (\Throwable) {
            return false;
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return true;
    }
}
