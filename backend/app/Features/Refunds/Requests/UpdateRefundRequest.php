<?php

namespace App\Features\Refunds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
