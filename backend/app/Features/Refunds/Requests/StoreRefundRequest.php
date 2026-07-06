<?php

namespace App\Features\Refunds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
