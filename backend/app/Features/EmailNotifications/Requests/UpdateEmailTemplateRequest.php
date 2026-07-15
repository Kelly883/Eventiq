<?php

namespace App\Features\EmailNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('emailTemplate')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'mjml_source' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
