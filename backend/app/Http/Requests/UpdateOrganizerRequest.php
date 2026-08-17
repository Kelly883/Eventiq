<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'displayName' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:500',
            'avatarUrl' => 'nullable|string|max:2048',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:2048',
            'socialLinks' => 'nullable|array',
            'socialLinks.twitter' => 'nullable|url|max:2048',
            'socialLinks.instagram' => 'nullable|url|max:2048',
            'socialLinks.linkedin' => 'nullable|url|max:2048',
            'socialLinks.youtube' => 'nullable|url|max:2048',
            'brandingColors' => 'nullable|array',
            'brandingColors.primaryColor' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'brandingColors.accentColor' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'timezone' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
            'country' => 'nullable|string|max:2',
            'verificationStatus' => 'nullable|string|max:50',
            'paymentDefault' => 'nullable|string|max:50',
            'commissionRate' => 'nullable|numeric|min:0|max:100',
            'isPublic' => 'sometimes|boolean',
            'emailPublic' => 'sometimes|boolean',
            'phonePublic' => 'sometimes|boolean',
            'hideSocialLinks' => 'sometimes|boolean',
            'hideBrandingColors' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'brandingColors.primaryColor.regex' => 'The primary color must be a valid hex color (e.g. #FF5733).',
            'brandingColors.accentColor.regex' => 'The accent color must be a valid hex color (e.g. #FF5733).',
            'socialLinks.*.url' => 'Each social link must be a valid URL.',
            'currency.size' => 'The currency must be a 3-letter ISO code (e.g. NGN, USD).',
            'country.size' => 'The country must be a 2-letter ISO code (e.g. NG, US).',
        ];
    }
}
