<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TODO: this table has no real columns yet. TicketDeliveryService::
        // sendViaDashboard() (app/Services/TicketDeliveryService.php) expects
        // user_id, ticket_reference, channel, payload (json) - add those
        // here when this feature is actually built out. Until then, that
        // service guards with Schema::hasColumn() and reports 'not checked'
        // rather than crashing or fabricating a result.
        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
    }
};
