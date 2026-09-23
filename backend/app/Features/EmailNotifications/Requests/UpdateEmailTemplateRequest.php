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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'html_body' => ['nullable', 'string'],
            'mjml_body' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
        ];
    }
}

