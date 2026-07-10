<?php

namespace App\Features\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestOrganizerPayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'organizer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string'],
        ];
    }
}

