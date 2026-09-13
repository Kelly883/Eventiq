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
        $hasCheckInsClientMutationId = Schema::hasColumn('check_ins', 'client_mutation_id');
        Schema::table('check_ins', function (Blueprint $table) use ($hasCheckInsClientMutationId) {
            if (!$hasCheckInsClientMutationId) {
                $table->string('client_mutation_id')->nullable()->unique()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasCheckInsClientMutationId = Schema::hasColumn('check_ins', 'client_mutation_id');
        Schema::table('check_ins', function (Blueprint $table) use ($hasCheckInsClientMutationId) {
            if ($hasCheckInsClientMutationId) {
                $table->dropColumn('client_mutation_id');
            }
        });
    }
};
