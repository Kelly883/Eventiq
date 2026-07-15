<?php

namespace App\Features\EmailNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Features\EmailNotifications\Models\EmailTemplate::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required_without:mjml_source', 'nullable', 'string'],
            'mjml_source' => ['required_without:body', 'nullable', 'string'],
        ];
    }
}
