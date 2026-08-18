<?php

namespace App\Features\EmailNotifications\Requests;

use App\Features\EmailNotifications\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('emailTemplate')) ?? false;
    }

    public function rules(): array
    {
        $allowedTypes = ['order_confirmation', 'event_reminder', 'ticket_delivery', 'check_in_confirmation', 'refund_notification'];

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:' . implode(',', $allowedTypes)],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'from_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'html_body' => ['sometimes', 'required', 'string'],
            'mjml_body' => ['sometimes', 'required', 'string'],
            'variables' => ['sometimes', 'required', 'array'],
            'variables.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
