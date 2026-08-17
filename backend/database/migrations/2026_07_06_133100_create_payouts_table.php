<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('payouts')) {
            return;
        }
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('settlement_policy_id')->nullable()->constrained('settlement_policies')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 50)->default('pending');
            $table->string('payout_method', 50)->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['organizer_id', 'status']);
            $table->index(['event_id']);
            $table->index(['status', 'created_at']);
            $table->index(['settlement_policy_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
};
