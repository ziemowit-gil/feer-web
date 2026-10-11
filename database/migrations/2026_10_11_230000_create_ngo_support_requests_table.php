<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zgłoszenia „Wsparcie technologiczne dla NGO” (odpłatna działalność pożytku publicznego).
 * Status i kanał kontaktu to zwykłe stringi (dozwolone wartości w modelu NgoSupportRequest) —
 * łatwiej dodać nowy status niż zmieniać enum w SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ngo_support_requests', function (Blueprint $table) {
            $table->id();

            // Dane kontaktowe i organizacja
            $table->string('name');
            $table->string('organization_name');
            $table->string('role', 60);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('website', 500)->nullable();

            // Zakres wsparcia i potrzeby
            $table->json('support_areas');
            $table->text('description');

            // Preferencje i status
            $table->string('preferred_contact_channel', 20)->default('email');
            $table->string('preferred_time', 200)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->boolean('privacy_consent')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngo_support_requests');
    }
};
