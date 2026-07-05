<?php

namespace App\Features\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePricingWindowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];
    }
}
