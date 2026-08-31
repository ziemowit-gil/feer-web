<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punkty pomocy (moduł "Mapa pomocy", szablon federation) — miejsca, w
 * których mieszkańcy mogą uzyskać wsparcie (żywność, schronienie,
 * poradnictwo itd.), prezentowane na mapie Leaflet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('inne');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('phone')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_points');
    }
};
