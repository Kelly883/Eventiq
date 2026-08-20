<?php

namespace App\Features\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizerPayoutMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organizer !== null;
    }

    public function rules(): array
    {
        return [
            'bank_code' => ['required', 'string'],
            'bank_name' => ['nullable', 'string'],
            'account_number' => ['required', 'string'],
            'account_name' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
