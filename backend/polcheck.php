<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach ([new App\Features\Checkout\Models\Ticket(), new App\Models\Event(), new App\Models\Organizer(), new App\Models\User(), new App\Models\Role()] as $m) {
    $p = Illuminate\Support\Facades\Gate::getPolicyFor($m);
    echo str_pad(class_basename($m), 12) . ' => ' . ($p ? get_class($p) : 'NO POLICY') . PHP_EOL;
}
