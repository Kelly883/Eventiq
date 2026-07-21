<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_date', 'start_datetime');
            $table->renameColumn('end_date', 'end_datetime');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_datetime', 'start_date');
            $table->renameColumn('end_datetime', 'end_date');
        });
    }
};