<?php

namespace Tests\Feature;

use App\Models\AccessibilityPreference;
use App\Models\LanguagePreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    public function test_guest_cannot_read_accessibility_preferences(): void
    {
        $this->getJson('/api/users/me/accessibility-preferences')
            ->assertStatus(401);
    }

    public function test_guest_cannot_update_accessibility_preferences(): void
    {
        $this->patchJson('/api/users/me/accessibility-preferences/update', ['fontSize' => 18])
            ->assertStatus(401);
    }

    public function test_guest_cannot_read_language_preferences(): void
    {
        $this->getJson('/api/users/me/language-preferences')
            ->assertStatus(401);
    }

    public function test_accessibility_defaults_returned_when_no_preferences_stored(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/accessibility-preferences')
            ->assertOk()
            ->assertJsonPath('fontSize', 16)
            ->assertJsonPath('highContrast', false)
            ->assertJsonPath('screenReaderOptimized', false)
            ->assertJsonPath('focusIndicatorEnhanced', false)
            ->assertJsonPath('motionReduced', false)
            ->assertJsonPath('lineHeight', 1.5)
            ->assertJsonPath('letterSpacing', 0)
            ->assertJsonPath('wordSpacing', 0)
            ->assertJsonPath('colorBlindnessMode', 'none');

        $this->assertDatabaseCount('accessibility_preferences', 0);
    }

    public function test_accessibility_preferences_persist_using_camel_case_contract(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/accessibility-preferences/update', [
                'fontSize' => 18,
                'highContrast' => true,
                'motionReduced' => true,
                'colorBlindnessMode' => 'deuteranopia',
            ])
            ->assertOk()
            ->assertJsonPath('fontSize', 18)
            ->assertJsonPath('highContrast', true)
            ->assertJsonPath('colorBlindnessMode', 'deuteranopia');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/accessibility-preferences')
            ->assertOk()
            ->assertJsonPath('fontSize', 18)
            ->assertJsonPath('highContrast', true)
            ->assertJsonPath('motionReduced', true)
            ->assertJsonPath('colorBlindnessMode', 'deuteranopia');

        $this->assertDatabaseHas('accessibility_preferences', [
            'user_id' => $user->id,
            'font_size' => 18,
            'high_contrast' => 1,
        ]);

        $stored = AccessibilityPreference::where('user_id', $user->id)->first();
        $this->assertNotNull($stored);
        $this->assertSame(18, $stored->toArray()['fontSize']);
    }

    public function test_language_defaults_created_and_returned(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/language-preferences')
            ->assertOk()
            ->assertJsonPath('language', 'en')
            ->assertJsonPath('region', 'US')
            ->assertJsonPath('dateFormat', 'MM/DD/YYYY')
            ->assertJsonPath('timeFormat', '12-hour')
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('numberFormat', 'period')
            ->assertJsonPath('rtlEnabled', false);

        $this->assertDatabaseHas('localization_preferences', ['user_id' => $user->id]);
    }

    public function test_language_preferences_persist_using_camel_case_contract(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/language-preferences/update', [
                'language' => 'fr',
                'region' => 'CA',
                'dateFormat' => 'DD/MM/YYYY',
                'timeFormat' => '24-hour',
                'currency' => 'EUR',
                'numberFormat' => 'comma',
                'rtlEnabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('language', 'fr')
            ->assertJsonPath('dateFormat', 'DD/MM/YYYY')
            ->assertJsonPath('rtlEnabled', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/language-preferences')
            ->assertOk()
            ->assertJsonPath('language', 'fr')
            ->assertJsonPath('region', 'CA')
            ->assertJsonPath('dateFormat', 'DD/MM/YYYY')
            ->assertJsonPath('timeFormat', '24-hour')
            ->assertJsonPath('currency', 'EUR')
            ->assertJsonPath('numberFormat', 'comma')
            ->assertJsonPath('rtlEnabled', true);

        $pref = LanguagePreference::where('user_id', $user->id)->first();
        $this->assertNotNull($pref);
        $this->assertSame('fr', $pref->toArray()['language']);
        $this->assertSame('DD/MM/YYYY', $pref->toArray()['dateFormat']);
    }

    public function test_language_preferences_reject_unsupported_language(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/language-preferences/update', ['language' => 'zz'])
            ->assertUnprocessable()
            ->assertInvalid(['language']);
    }
}