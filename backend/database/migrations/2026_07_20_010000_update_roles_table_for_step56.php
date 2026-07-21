<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // UUID primary key instead of auto-increment
            $table->uuid('uuid')->nullable()->after('id');
            $table->json('permissions')->nullable()->after('description');
            $table->boolean('isSystemRole')->default(false)->after('permissions');
            $table->index('name');
            $table->index('isSystemRole');
        });

        // Migrate existing data and swap primary key in a second step is complex;
        // We'll keep the existing FK side simple for now by adding a uuid column
        // and indexing name/isSystemRole as required.
        // If strict UUID PK is required later, a dedicated data migration can
        // populate uuid and adjust foreign keys.
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['isSystemRole']);
            $table->dropColumn(['uuid', 'permissions', 'isSystemRole']);
        });
    }
};