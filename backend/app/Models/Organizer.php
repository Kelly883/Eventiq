<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organizer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'bio',
        'branding_color',
        'logo_path',
        'website_url',
        'social_links',
        'privacy_settings',
        'paystack_subaccount_code',
        'flutterwave_subaccount_id',
        'paystack_connect_status',
        'flutterwave_connect_status',
    ];

    protected $casts = [
        'social_links' => 'array',
        'privacy_settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function payoutMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Features\Payment\Models\OrganizerPayoutMethod::class);
    }

    public function apiKeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ApiKey::class);
    }

    public function getPublicProfile(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'business_name' => $this->business_name,
            'bio' => $this->bio,
            'branding_color' => $this->branding_color,
            'logo_path' => $this->logo_path,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getPrivateProfile(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'business_name' => $this->business_name,
            'bio' => $this->bio,
            'branding_color' => $this->branding_color,
            'logo_path' => $this->logo_path,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'privacy_settings' => $this->privacy_settings,
            'paystack_subaccount_code' => $this->paystack_subaccount_code,
            'flutterwave_subaccount_id' => $this->flutterwave_subaccount_id,
            'paystack_connect_status' => $this->paystack_connect_status,
            'flutterwave_connect_status' => $this->flutterwave_connect_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
