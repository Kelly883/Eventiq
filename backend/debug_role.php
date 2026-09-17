<?php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$app->make('Illuminate\Contracts\Console\Kernel')->call('migrate:refresh --force');

use App\Models\User;
use App\Models\Role;
use App\Models\Organizer;
use App\Models\Event;

// Set up exactly as the test does now
$organizerRole = Role::factory()->create(['name' => 'organizer']);
$user = User::factory()->create(['role_id' => $organizerRole->id]);
$organizer = Organizer::factory()->create(['user_id' => $user->id]);
$event = Event::factory()->create(['organizer_id' => $organizer->id]);

// Check user hasRole
echo "User hasRole('organizer'): " . ($user->hasRole('organizer') ? 'true' : 'false') . "\n";
echo "User hasRole('admin'): " . ($user->hasRole('admin') ? 'true' : 'false') . "\n";
echo "User hasRole('super-admin'): " . ($user->hasRole('super-admin') ? 'true' : 'false') . "\n";

// Check the role relation
$roleRelation = $user->roleRelation;
echo "roleRelation: " . ($roleRelation ? 'found' : 'null') . "\n";
if ($roleRelation) {
    echo "  ->name: " . $roleRelation->name . "\n";
}

// Check roles relationship
$roles = $user->roles();
echo "roles count: " . $roles->count() . "\n";
$roles->each(function($r) {
    echo "  Role: id=" . $r->id . " name='" . $r->name . "'\n";
});

// Check organizer relationship
echo "user->organizer: " . ($user->organizer ? 'found' : 'null') . "\n";
if ($user->organizer) {
    echo "  ->id: " . $user->organizer->id . "\n";
    echo "  ->user_id: " . $user->organizer->user_id . "\n";
}

// Check event organizer
echo "event->organizer: " . ($event->organizer ? 'found' : 'null') . "\n";
if ($event->organizer) {
    echo "  ->id: " . $event->organizer->id . "\n";
    echo "  ->user_id: " . $event->organizer->user_id . "\n";
}

// Now simulate the policy's ownsEvent
$ownsEvent = $event
    && $event->organizer
    && (string) $event->organizer->user_id === (string) $user->id;
echo "ownsEvent result: " . ($ownsEvent ? 'true' : 'false') . "\n";

// Simulate the policy's restore method
$hasRole = $user->hasRole('organizer') || $user->hasRole('admin') || $user->hasRole('super-admin');
echo "has required role for restore: " . ($hasRole ? 'true' : 'false') . "\n";

// Final: would the policy allow the restore?
$policyAllows = $hasRole && $ownsEvent;
echo "Policy would allow restore: " . ($policyAllows ? 'true' : 'false') . "\n";
