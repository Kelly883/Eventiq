<?php

namespace App\Features\EmailNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
