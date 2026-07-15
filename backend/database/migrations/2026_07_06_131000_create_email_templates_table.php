<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('body'); // compiled HTML (from MJML, or raw HTML if not using MJML)
            $table->text('mjml_source')->nullable(); // raw MJML markup, when applicable
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_templates');
    }
};
