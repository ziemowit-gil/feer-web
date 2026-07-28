<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // Dodatkowe pola formularza definiowane w panelu: [{key,label,type,required,options}]
            $table->json('form_fields')->nullable()->after('form_consent_label');
        });

        Schema::table('webinar_registrations', function (Blueprint $table) {
            // Wartości dodatkowych pól (klucz => wartość) — przekazywane do API.
            $table->json('extra')->nullable()->after('consent');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', fn (Blueprint $t) => $t->dropColumn('form_fields'));
        Schema::table('webinar_registrations', fn (Blueprint $t) => $t->dropColumn('extra'));
    }
};
