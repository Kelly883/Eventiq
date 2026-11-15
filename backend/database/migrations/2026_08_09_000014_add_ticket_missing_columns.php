<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'seat_number')) {
                $table->string('seat_number')->nullable()->after('attendee_email');
            }
            if (! Schema::hasColumn('tickets', 'section')) {
                $table->string('section')->nullable()->after('seat_number');
            }
            if (! Schema::hasColumn('tickets', 'row')) {
                $table->string('row')->nullable()->after('section');
            }
            if (! Schema::hasColumn('tickets', 'price_paid')) {
                $table->decimal('price_paid', 10, 2)->nullable()->after('row');
            }
            if (! Schema::hasColumn('tickets', 'currency')) {
                $table->string('currency', 3)->nullable()->after('price_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'seat_number',
                'section',
                'row',
                'price_paid',
                'currency',
            ]);
        });
    }
};
