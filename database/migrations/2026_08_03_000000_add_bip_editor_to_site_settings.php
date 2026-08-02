<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('bip_editor_name')->nullable()->after('bip_intro');
            $table->string('bip_editor_email')->nullable()->after('bip_editor_name');
            $table->string('bip_gov_url')->nullable()->after('bip_editor_email');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['bip_editor_name', 'bip_editor_email', 'bip_gov_url']);
        });
    }
};
