<?php

namespace App\Features\EmailNotifications\Requests;

use App\Features\EmailNotifications\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailTemplate::class) ?? false;
    }

    public function rules(): array
    {
        $allowedTypes = ['order_confirmation', 'event_reminder', 'ticket_delivery', 'check_in_confirmation', 'refund_notification'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', $allowedTypes)],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'mjml_body' => ['required', 'string'],
            'variables' => ['required', 'array'],
            'variables.*' => ['string'],
            'is_active' => ['boolean'],
        ];
    }
}
