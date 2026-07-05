<?php

namespace App\Features\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartVerifyRequest extends FormRequest
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
