<?php

namespace App\Features\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingWindowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'end_date' => 'sometimes|date|after:start_date',
        ];
    }
}
