<?php

namespace App\Features\CheckIn\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
