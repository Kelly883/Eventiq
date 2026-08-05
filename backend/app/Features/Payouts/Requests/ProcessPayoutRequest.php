<?php

namespace App\Features\Payouts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPayoutRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasRole('admin');
    }

    public function rules()
    {
        return [
            'transaction_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
