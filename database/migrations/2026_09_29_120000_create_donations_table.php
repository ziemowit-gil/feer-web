<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Darowizny jednorazowe przyjmowane przez stronę (Przelewy24). Źródło prawdy
 * po stronie CMS-a: wpłata zapisuje się tu zawsze, a rejestr darowizn w SZO
 * dostaje ją dopiero po potwierdzeniu w P24 (kolumny szo_*, ponawiane
 * poleceniem donations:sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->uuid('session_id')->unique();
            $table->unsignedInteger('amount_grosze');
            $table->string('currency', 3)->default('PLN');
            $table->string('status', 20)->default('pending')->index();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            // Publiczna lista „Ostatnie wpłaty": imię + inicjał albo „Wpłata anonimowa".
            $table->boolean('is_anonymous')->default(true);
            $table->boolean('consent_newsletter')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('p24_order_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('szo_donation_id')->nullable();
            $table->unsignedBigInteger('szo_contact_id')->nullable();
            $table->timestamp('szo_synced_at')->nullable();
            $table->text('szo_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
