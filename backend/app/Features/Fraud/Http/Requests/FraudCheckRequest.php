<?php

namespace App\Features\Fraud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FraudCheckRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
