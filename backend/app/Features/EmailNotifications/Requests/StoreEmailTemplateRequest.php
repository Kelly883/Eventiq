<?php

namespace App\Features\EmailNotifications\Requests;

use App\Features\EmailNotifications\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorizing here is secondary; the route middleware
        // ('bearer' + 'role:admin') and the controller's policy check both
        // enforce admin access. This guard is a defence-in-depth fallback for
        // any code path that bypasses the middleware.
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['nullable', 'string'],
            'mjml_body' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
        ];
    }
}

