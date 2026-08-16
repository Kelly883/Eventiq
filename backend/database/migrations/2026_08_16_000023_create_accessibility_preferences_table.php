<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accessibility_preferences')) {
            return;
        }

        Schema::create('accessibility_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id');
            $table->integer('font_size')->default(16);
            $table->boolean('high_contrast')->default(false);
            $table->boolean('screen_reader_optimized')->default(false);
            $table->boolean('focus_indicator_enhanced')->default(false);
            $table->boolean('motion_reduced')->default(false);
            $table->decimal('line_height', 3, 2)->default(1.5);
            $table->decimal('letter_spacing', 4, 3)->default(0);
            $table->decimal('word_spacing', 4, 3)->default(0);
            $table->string('color_blindness_mode')->default('none');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessibility_preferences');
    }
};
