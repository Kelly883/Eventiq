<?php

namespace App\Features\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateComplianceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportCode' => ['required', 'string'],
            'filters' => ['nullable', 'array'],
        ];
    }
}

