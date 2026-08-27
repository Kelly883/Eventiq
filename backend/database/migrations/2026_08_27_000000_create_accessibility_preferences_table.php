<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateAccessibilityPreferencesTable extends Migration {
    public function up() {
        Schema::create('accessibility_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId');
            $table->integer('fontSize')->default(16);
            $table->boolean('highContrast')->default(false);
            $table->boolean('screenReaderOptimized')->default(false);
            $table->boolean('focusIndicatorEnhanced')->default(false);
            $table->boolean('motionReduced')->default(false);
            $table->decimal('lineHeight',3,2)->default(1.50);
            $table->decimal('letterSpacing',4,3)->default(0);
            $table->decimal('wordSpacing',4,3)->default(0);
            $table->string('colorBlindnessMode')->default('none');
            $table->timestamps();
            $table->index('userId');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down() { Schema::dropIfExists('accessibility_preferences'); }
}
