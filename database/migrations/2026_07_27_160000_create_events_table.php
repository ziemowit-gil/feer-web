<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nadchodzące szkolenia i wydarzenia: pojedyncze pozycje z terminem
     * (starts_at/ends_at), trybem (stacjonarnie/zdalnie/hybrydowo) i osobnym,
     * edytowalnym linkiem do zapisów (zewnętrzny formularz albo mailto na
     * podstawie adresu kontaktowego). Lista publiczna pokazuje tylko te, które
     * jeszcze się nie zakończyły — stąd zapytania po ends_at/starts_at.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('lead', 300);
            $table->text('description')->nullable();

            $table->string('type')->default('szkolenie');   // szkolenie|warsztat|webinar|wydarzenie
            $table->string('mode')->default('stacjonarnie'); // stacjonarnie|zdalnie|hybrydowo
            $table->string('location')->nullable();          // miejsce (dla trybu stacjonarnego/hybrydowego)
            $table->string('online_url')->nullable();        // link do spotkania online (dla zdalnych/hybrydowych)

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();

            $table->string('registration_url')->nullable();
            $table->string('registration_cta_label')->default('Zapisz się');
            $table->string('contact_email')->nullable();
            $table->string('price_info')->nullable();        // np. „Bezpłatne" / „50 zł"

            $table->string('audience')->default('brand');    // schemat kolorów (spójny z projektami)
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
