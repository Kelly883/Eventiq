<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('email_templates', 'from_name')) {
                $table->string('from_name')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('email_templates', 'from_email')) {
                $table->string('from_email')->nullable()->after('from_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['from_name', 'from_email']);
        });
    }
};
