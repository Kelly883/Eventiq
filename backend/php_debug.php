<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Event;
use App\Models\User;
use App\Models\Organizer;

// Create test data manually
$user = User::factory()->create(["role" => "organizer"]);
$organizer = Organizer::factory()->create(["user_id" => $user->id]);
$event = Event::factory()->create(["organizer_id" => $organizer->id]);

// Now run the exact query from authorizeEventOwner
$eventId = $event->id;
$result = Event::where("id", $eventId)
    ->whereHas("organizer", function ($q) use ($user) {
        $q->where("user_id", $user->id);
    })
    ->exists();

var_dump("User id: " . $user->id);
var_dump("Organizer id: " . $organizer->id);
var_dump("Organizer user_id: " . $organizer->user_id);
var_dump("Event id: " . $event->id);
var_dump("Event organizer_id: " . $event->organizer_id);
var_dump("Query result: " . ($result ? "true" : "false"));

// Show the SQL
$query = Event::where("id", $eventId)
    ->whereHas("organizer", function ($q) use ($user) {
        $q->where("user_id", $user->id);
    })
    ->toSql();

echo "SQL: " . $query . "\n";
$bindings = Event::where("id", $eventId)
    ->whereHas("organizer", function ($q) use ($user) {
        $q->where("user_id", $user->id);
    })
    ->getBindings();
var_dump("Bindings: " . json_encode($bindings));
