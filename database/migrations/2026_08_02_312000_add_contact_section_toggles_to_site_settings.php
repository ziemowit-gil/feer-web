<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('contact_show_form')->default(true)->after('contact_intro');
            $table->boolean('contact_show_bank_accounts')->default(true)->after('contact_bank_accounts');
            $table->boolean('contact_show_coordinators')->default(true)->after('contact_show_bank_accounts');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_show_form', 'contact_show_bank_accounts', 'contact_show_coordinators']);
        });
    }
};
