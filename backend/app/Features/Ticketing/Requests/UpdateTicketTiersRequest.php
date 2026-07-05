<?php

namespace App\Features\Ticketing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTiersRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tiers' => 'required|array',
        ];
    }
}
