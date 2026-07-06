<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_report_generations', function (Blueprint $table) {
            $table->id();
            $table->string('report_code');
            $table->string('status')->default('queued'); // queued|processing|ready|failed
            $table->foreignId('requested_by')->nullable()->constrained()->nullOnDelete();
            $table->json('filters')->nullable();
            $table->string('result_location')->nullable(); // path/url to export
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_report_generations');
    }
};

