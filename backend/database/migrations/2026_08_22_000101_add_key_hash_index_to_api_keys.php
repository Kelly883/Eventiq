<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_keys') && ! Schema::hasColumn('api_keys', 'key_hash_index')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('key_hash_index', 64)->nullable()->after('hashed_key');
                $table->index('key_hash_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('api_keys') && Schema::hasColumn('api_keys', 'key_hash_index')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->dropIndex(['key_hash_index']);
                $table->dropColumn('key_hash_index');
            });
        }
    }
};
