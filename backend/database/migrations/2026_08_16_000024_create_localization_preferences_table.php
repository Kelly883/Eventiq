<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('localization_preferences')) {
            return;
        }

        Schema::create('localization_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id');
            $table->string('language')->default('en');
            $table->string('region')->nullable();
            $table->string('date_format')->default('MM/DD/YYYY');
            $table->string('time_format')->default('12-hour');
            $table->string('currency')->nullable();
            $table->string('number_format')->default('comma');
            $table->boolean('rtl_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localization_preferences');
    }
};
