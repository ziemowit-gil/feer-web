<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zgłoszenia przystąpienia do federacji (szablon "federation") — organizacja
 * przesyła dane kontaktowe wraz ze skanami dokumentów (deklaracja, uchwała, statut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federation_membership_applications', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federation_membership_applications');
    }
};
