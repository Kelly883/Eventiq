<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_preferences_exists(): void
    {
        $exists = \Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='delivery_preferences'");
        $this->assertNotEmpty($exists, 'delivery_preferences table should exist');
    }
}
