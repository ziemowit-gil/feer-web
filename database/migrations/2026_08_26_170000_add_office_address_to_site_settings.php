<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Adres biura / do korespondencji — inny niż rejestrowy (contact_address).
            $table->string('contact_office_address')->nullable()->after('contact_city');
            $table->string('contact_office_city')->nullable()->after('contact_office_address');
            $table->text('contact_office_note')->nullable()->after('contact_office_city');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_office_address', 'contact_office_city', 'contact_office_note']);
        });
    }
};
