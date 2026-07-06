<?php

namespace App\Features\PushNotifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePushTemplateRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
