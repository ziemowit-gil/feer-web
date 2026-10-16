<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dowolne bloki (edytor blokowy) w stałych miejscach stron list — np. pod menu kategorii
 * na stronie „Działania”. Jeden rekord na miejsce (`key`) i witrynę.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('key', 40);
            $table->string('title');
            $table->string('meta_description', 500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->json('blocks')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_sections');
    }
};
