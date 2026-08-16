<?php

namespace App\Features\OrganizerProfile\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => 'sometimes|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'branding_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'logo_path' => 'nullable|string|max:2048',
            'avatar_url' => 'nullable|url|max:2048',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website_url' => 'nullable|url|max:2048',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:2048',
            'social_links.twitter' => 'nullable|url|max:2048',
            'social_links.instagram' => 'nullable|url|max:2048',
            'social_links.linkedin' => 'nullable|url|max:2048',
            'social_links.youtube' => 'nullable|url|max:2048',
            'privacy_settings' => 'nullable|array',
            'privacy_settings.show_email' => 'boolean',
            'privacy_settings.show_phone' => 'boolean',
            'privacy_settings.show_social_links' => 'boolean',
            'privacy_settings.show_past_events' => 'boolean',
            'privacy_settings.show_upcoming_events' => 'boolean',
            'is_public' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branding_color.regex' => 'The branding color must be a valid hex color (e.g. #FF5733).',
            'social_links.*.url' => 'Each social link must be a valid URL.',
        ];
    }
}
