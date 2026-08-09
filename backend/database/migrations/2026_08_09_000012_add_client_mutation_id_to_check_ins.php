<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            if (! Schema::hasColumn('check_ins', 'client_mutation_id')) {
                $table->string('client_mutation_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            if (Schema::hasColumn('check_ins', 'client_mutation_id')) {
                $table->dropColumn('client_mutation_id');
            }
        });
    }
};
