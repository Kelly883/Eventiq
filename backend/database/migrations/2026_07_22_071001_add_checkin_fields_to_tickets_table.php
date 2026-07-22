<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('ticket_id')->unique()->after('id');
            $table->string('attendee_name')->after('ticket_id');
            $table->string('attendee_email')->after('attendee_name');
            $table->string('tier')->after('attendee_email');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['ticket_id', 'attendee_name', 'attendee_email', 'tier']);
        });
    }
};