<?php

namespace App\Features\ApiKeys\Requests;

use App\Features\ApiKeys\Enums\ApiKeyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organizer !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => [
                'string',
                Rule::in(ApiKeyScope::availableValues()),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scopes.*.in' => 'The scope [:input] cannot be granted. The API is read-only today; write scopes are reserved for a future mutation surface.',
        ];
    }
}
