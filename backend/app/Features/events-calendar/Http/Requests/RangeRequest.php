<?php

namespace App\Features\EventsCalendar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RangeRequest extends FormRequest
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
