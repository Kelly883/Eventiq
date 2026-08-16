<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$container = new Container();
$events = new Dispatcher($container);
Capsule::getContainer()->instance('events', $events);

// Run migrations
$migrationPaths = [
    __DIR__ . '/../../../backend/database/migrations',
];

foreach ($migrationPaths as $path) {
    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        if (strpos($file, 'organizer') !== false || strpos($file, 'user') !== false) {
            require $file;
            $className = basename($file, '.php');
            $className = str_replace(['-', '_'], ['', ''], ucwords($className, '_-'));
            $migration = new $className;
            if (method_exists($migration, 'up')) {
                $migration->up();
            }
        }
    }
}

// Create tables if migrations didn't run properly
$schema = $capsule->schema();

if (!$schema->hasTable('users')) {
    $schema->create('users', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('passwordHash');
        $table->string('role')->default('attendee');
        $table->timestamps();
    });
}

if (!$schema->hasTable('organizers')) {
    $schema->create('organizers', function ($table) {
        $table->id();
        $table->uuid('user_id')->unique();
        $table->string('business_name')->nullable();
        $table->text('bio')->nullable();
        $table->string('branding_color', 7)->nullable();
        $table->string('logo_path')->nullable();
        $table->string('website_url')->nullable();
        $table->json('social_links')->nullable();
        $table->json('privacy_settings')->nullable();
        $table->string('paystack_subaccount_code')->nullable();
        $table->string('flutterwave_subaccount_id')->nullable();
        $table->string('paystack_connect_status')->nullable();
        $table->string('flutterwave_connect_status')->nullable();
        $table->timestamp('deleted_at')->nullable();
        $table->timestamps();
    });
}

// Test 1: Create organizer and verify fillable
echo "=== Test 1: Fillable properties ===\n";
$userData = [
    'id' => '123e4567-e89b-12d3-a456-426614174000',
    'name' => 'Test Organizer',
    'email' => 'test@example.com',
    'passwordHash' => password_hash('password', PASSWORD_BCRYPT),
    'role' => 'organizer',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
];
Capsule::table('users')->insert($userData);

$organizer = new App\Models\Organizer();
$organizer->user_id = $userData['id'];
$organizer->business_name = 'Test Business';
$organizer->bio = 'Test bio';
$organizer->branding_color = '#FF5733';
$organizer->logo_path = '/logos/test.png';
$organizer->website_url = 'https://test.com';
$organizer->social_links = json_encode(['twitter' => 'https://twitter.com/test']);
$organizer->privacy_settings = json_encode(['show_email' => true]);
$organizer->paystack_subaccount_code = 'SUB_123';
$organizer->flutterwave_subaccount_id = 'FLW_456';
$organizer->paystack_connect_status = 'verified';
$organizer->flutterwave_connect_status = 'pending';
$organizer->save();

echo "Organizer created with ID: " . $organizer->id . "\n";
echo "business_name: " . $organizer->business_name . "\n";
echo "paystack_subaccount_code: " . $organizer->paystack_subaccount_code . "\n";

// Test 2: getPublicProfile excludes sensitive fields
echo "\n=== Test 2: getPublicProfile() ===\n";
$public = $organizer->getPublicProfile();
echo "Public keys: " . implode(', ', array_keys($public)) . "\n";
$sensitiveKeys = ['privacy_settings', 'paystack_subaccount_code', 'flutterwave_subaccount_id', 'paystack_connect_status', 'flutterwave_connect_status', 'deleted_at'];
foreach ($sensitiveKeys as $key) {
    if (isset($public[$key])) {
        echo "FAIL: $key should not be in public profile\n";
    } else {
        echo "PASS: $key excluded from public profile\n";
    }
}

// Test 3: getPrivateProfile includes all fields
echo "\n=== Test 3: getPrivateProfile() ===\n";
$private = $organizer->getPrivateProfile();
echo "Private keys count: " . count($private) . "\n";
$expectedKeys = ['id', 'user_id', 'business_name', 'bio', 'branding_color', 'logo_path', 'website_url', 'social_links', 'privacy_settings', 'paystack_subaccount_code', 'flutterwave_subaccount_id', 'paystack_connect_status', 'flutterwave_connect_status', 'created_at', 'updated_at', 'deleted_at'];
foreach ($expectedKeys as $key) {
    if (isset($private[$key])) {
        echo "PASS: $key present in private profile\n";
    } else {
        echo "FAIL: $key missing from private profile\n";
    }
}

// Test 4: User relationship
echo "\n=== Test 4: User relationship ===\n";
$relatedUser = $organizer->user;
if ($relatedUser && $relatedUser->id === $userData['id']) {
    echo "PASS: User relationship works\n";
} else {
    echo "FAIL: User relationship broken\n";
}

// Test 5: Soft deletes
echo "\n=== Test 5: Soft deletes ===\n";
$organizer->delete();
if ($organizer->deleted_at !== null) {
    echo "PASS: Soft delete sets deleted_at\n";
} else {
    echo "FAIL: Soft delete did not set deleted_at\n";
}

$found = App\Models\Organizer::withTrashed()->find($organizer->id);
if ($found) {
    echo "PASS: Soft deleted record found with withTrashed()\n";
} else {
    echo "FAIL: Soft deleted record not found\n";
}

$notFound = App\Models\Organizer::find($organizer->id);
if (!$notFound) {
    echo "PASS: Soft deleted record not found in default query\n";
} else {
    echo "FAIL: Soft deleted record still found in default query\n";
}

echo "\n=== All tests completed ===\n";
