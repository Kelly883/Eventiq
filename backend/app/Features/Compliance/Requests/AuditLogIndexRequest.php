<?php

namespace App\Features\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin-only is enforced in middleware/policy at controller/route level.
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ];
    }
}

