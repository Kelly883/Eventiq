<?php

namespace App\Features\EmailNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
