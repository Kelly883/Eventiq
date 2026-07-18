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
            'action' => ['nullable', 'string'],
            'entity' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

