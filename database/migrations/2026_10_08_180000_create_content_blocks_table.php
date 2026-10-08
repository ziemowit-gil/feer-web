<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bloki treści z kreatora w edytorze (przyciski CTA, akordeony), wstawiane shortcodem [blok:ID].
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('type', 20); // cta | accordion
            $table->string('name');
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
