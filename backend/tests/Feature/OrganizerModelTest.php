<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_has_fillable_properties(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test Organizer',
            'bio' => 'Test bio',
            'branding_color' => '#FF5733',
            'logo_path' => '/logos/test.png',
            'avatar_url' => 'https://example.com/avatar.png',
            'email' => 'test@example.com',
            'phone' => '+234801234567',
            'website_url' => 'https://test.com',
            'social_links' => ['twitter' => 'https://twitter.com/test'],
            'privacy_settings' => ['show_email' => true],
            'is_public' => true,
            'paystack_subaccount_code' => 'SUB_123',
            'flutterwave_subaccount_id' => 'FLW_456',
            'paystack_connect_status' => 'verified',
            'flutterwave_connect_status' => 'pending',
        ]);

        $this->assertEquals('Test Business', $organizer->business_name);
        $this->assertEquals('Test Organizer', $organizer->display_name);
        $this->assertEquals('Test bio', $organizer->bio);
        $this->assertEquals('#FF5733', $organizer->branding_color);
        $this->assertEquals('https://example.com/avatar.png', $organizer->avatar_url);
        $this->assertEquals('test@example.com', $organizer->email);
        $this->assertEquals('+234801234567', $organizer->phone);
        $this->assertTrue($organizer->is_public);
    }

    public function test_get_public_profile_returns_only_safe_fields(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test Organizer',
            'bio' => 'Test bio',
            'branding_color' => '#FF5733',
            'logo_path' => '/logos/test.png',
            'avatar_url' => 'https://example.com/avatar.png',
            'email' => 'test@example.com',
            'phone' => '+234801234567',
            'website_url' => 'https://test.com',
            'social_links' => ['twitter' => 'https://twitter.com/test'],
            'privacy_settings' => ['show_email' => true, 'show_phone' => false, 'show_social_links' => true],
            'is_public' => true,
            'paystack_subaccount_code' => 'SUB_123',
            'flutterwave_subaccount_id' => 'FLW_456',
            'paystack_connect_status' => 'verified',
            'flutterwave_connect_status' => 'pending',
        ]);

        $publicProfile = $organizer->getPublicProfile();

        $this->assertArrayHasKey('id', $publicProfile);
        $this->assertArrayHasKey('business_name', $publicProfile);
        $this->assertArrayHasKey('display_name', $publicProfile);
        $this->assertArrayHasKey('bio', $publicProfile);
        $this->assertArrayHasKey('branding_color', $publicProfile);
        $this->assertArrayHasKey('logo_path', $publicProfile);
        $this->assertArrayHasKey('avatar_url', $publicProfile);
        $this->assertArrayHasKey('website_url', $publicProfile);
        $this->assertArrayHasKey('social_links', $publicProfile);
        $this->assertArrayHasKey('is_public', $publicProfile);
        $this->assertArrayHasKey('created_at', $publicProfile);
        $this->assertArrayHasKey('updated_at', $publicProfile);

        $this->assertArrayNotHasKey('privacy_settings', $publicProfile);
        $this->assertArrayNotHasKey('paystack_subaccount_code', $publicProfile);
        $this->assertArrayNotHasKey('flutterwave_subaccount_id', $publicProfile);
        $this->assertArrayNotHasKey('paystack_connect_status', $publicProfile);
        $this->assertArrayNotHasKey('flutterwave_connect_status', $publicProfile);
        $this->assertArrayNotHasKey('deleted_at', $publicProfile);

        $this->assertEquals('test@example.com', $publicProfile['email']);
        $this->assertNull($publicProfile['phone']);
    }

    public function test_get_public_profile_respects_privacy_settings(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test Organizer',
            'email' => 'test@example.com',
            'phone' => '+234801234567',
            'social_links' => ['twitter' => 'https://twitter.com/test'],
            'privacy_settings' => ['show_email' => false, 'show_phone' => false, 'show_social_links' => false],
            'is_public' => true,
        ]);

        $publicProfile = $organizer->getPublicProfile();

        $this->assertNull($publicProfile['email']);
        $this->assertNull($publicProfile['phone']);
        $this->assertNull($publicProfile['social_links']);
    }

    public function test_get_private_profile_returns_all_fields(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test Organizer',
            'bio' => 'Test bio',
            'branding_color' => '#FF5733',
            'logo_path' => '/logos/test.png',
            'avatar_url' => 'https://example.com/avatar.png',
            'email' => 'test@example.com',
            'phone' => '+234801234567',
            'website_url' => 'https://test.com',
            'social_links' => ['twitter' => 'https://twitter.com/test'],
            'privacy_settings' => ['show_email' => true],
            'is_public' => true,
            'paystack_subaccount_code' => 'SUB_123',
            'flutterwave_subaccount_id' => 'FLW_456',
            'paystack_connect_status' => 'verified',
            'flutterwave_connect_status' => 'pending',
        ]);

        $privateProfile = $organizer->getPrivateProfile();

        $this->assertArrayHasKey('id', $privateProfile);
        $this->assertArrayHasKey('user_id', $privateProfile);
        $this->assertArrayHasKey('business_name', $privateProfile);
        $this->assertArrayHasKey('display_name', $privateProfile);
        $this->assertArrayHasKey('bio', $privateProfile);
        $this->assertArrayHasKey('branding_color', $privateProfile);
        $this->assertArrayHasKey('logo_path', $privateProfile);
        $this->assertArrayHasKey('avatar_url', $privateProfile);
        $this->assertArrayHasKey('email', $privateProfile);
        $this->assertArrayHasKey('phone', $privateProfile);
        $this->assertArrayHasKey('website_url', $privateProfile);
        $this->assertArrayHasKey('social_links', $privateProfile);
        $this->assertArrayHasKey('privacy_settings', $privateProfile);
        $this->assertArrayHasKey('is_public', $privateProfile);
        $this->assertArrayHasKey('paystack_subaccount_code', $privateProfile);
        $this->assertArrayHasKey('flutterwave_subaccount_id', $privateProfile);
        $this->assertArrayHasKey('paystack_connect_status', $privateProfile);
        $this->assertArrayHasKey('flutterwave_connect_status', $privateProfile);
        $this->assertArrayHasKey('created_at', $privateProfile);
        $this->assertArrayHasKey('updated_at', $privateProfile);
        $this->assertArrayHasKey('deleted_at', $privateProfile);
    }

    public function test_user_relationship_is_defined(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $organizer->user());
        $this->assertEquals($user->id, $organizer->user->id);
    }

    public function test_soft_deletes_are_enabled(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
        ]);

        $organizer->delete();

        $this->assertNotNull($organizer->deleted_at);
        $this->assertSoftDeleted('organizers', ['id' => $organizer->id]);
    }

    public function test_organizer_public_has_fewer_fields_than_organizer(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test Organizer',
            'bio' => 'Test bio',
            'branding_color' => '#FF5733',
            'logo_path' => '/logos/test.png',
            'avatar_url' => 'https://example.com/avatar.png',
            'email' => 'test@example.com',
            'phone' => '+234801234567',
            'website_url' => 'https://test.com',
            'social_links' => ['twitter' => 'https://twitter.com/test'],
            'privacy_settings' => ['show_email' => true],
            'is_public' => true,
            'paystack_subaccount_code' => 'SUB_123',
            'flutterwave_subaccount_id' => 'FLW_456',
            'paystack_connect_status' => 'verified',
            'flutterwave_connect_status' => 'pending',
        ]);

        $publicKeys = array_keys($organizer->getPublicProfile());
        $privateKeys = array_keys($organizer->getPrivateProfile());

        $this->assertCount(count($publicKeys), array_unique($publicKeys));
        $this->assertCount(count($privateKeys), array_unique($privateKeys));
        $this->assertLessThan(count($privateKeys), count($publicKeys));
    }
}
