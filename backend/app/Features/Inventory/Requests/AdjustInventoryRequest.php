<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tierIdOrWindowId' => 'required|string',
            'newQuantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
