<?php

namespace App\Features\Payouts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
