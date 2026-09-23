<?php

namespace App\Features\EmailNotifications\Requests;

use App\Features\EmailNotifications\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Defence-in-depth admin gate. The route param is the template ID, not a
        // resolved model, so the policy's update(User, EmailTemplate) method
        // would TypeError when invoked here with a scalar. The controller
        // performs the real per-resource policy check after resolving the model.
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $allowedTypes = [
            'order_confirmation', 'event_reminder', 'ticket_delivery',
            'check_in_confirmation', 'refund_notification',
        ];

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:' . implode(',', $allowedTypes)],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'html_body' => ['sometimes', 'required', 'string'],
            'mjml_body' => ['sometimes', 'nullable', 'string'],
            'variables' => ['sometimes', 'required', 'array'],
            'variables.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

