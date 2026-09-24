<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_templates', function (Blueprint $table) {
            $columns = [
                'priority' => fn($t) => $t->integer('priority')->default(0),
                'badge' => fn($t) => $t->integer('badge')->default(1),
                'sound' => fn($t) => $t->string('sound', 255)->default('default'),
                'click_action' => fn($t) => $t->string('click_action', 255)->nullable(),
                'collapse_key' => fn($t) => $t->string('collapse_key', 255)->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('push_notification_templates', $name)) {
                    $definition($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_templates', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'badge',
                'sound',
                'click_action',
                'collapse_key',
            ]);
        });
    }
};
