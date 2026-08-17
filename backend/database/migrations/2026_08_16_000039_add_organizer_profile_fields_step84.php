<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('timezone')->nullable()->after('deletedAt');
            $table->string('currency', 3)->nullable()->after('timezone');
            $table->string('country', 2)->nullable()->after('currency');
            $table->string('verificationStatus')->nullable()->after('country');
            $table->string('paymentDefault')->nullable()->after('verificationStatus');
            $table->decimal('commissionRate', 5, 2)->nullable()->after('paymentDefault');
            $table->boolean('hideSocialLinks')->default(false)->after('phonePublic');
            $table->boolean('hideBrandingColors')->default(false)->after('hideSocialLinks');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'currency',
                'country',
                'verificationStatus',
                'paymentDefault',
                'commissionRate',
                'hideSocialLinks',
                'hideBrandingColors',
            ]);
        });
    }
};
