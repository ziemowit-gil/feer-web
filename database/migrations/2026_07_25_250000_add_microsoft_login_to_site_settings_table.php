<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('microsoft_login_enabled')->default(false)->after('content_editor');
            $table->string('microsoft_client_id')->nullable()->after('microsoft_login_enabled');
            // Zaszyfrowany w aplikacji (cast "encrypted"), dlatego typ text.
            $table->text('microsoft_client_secret')->nullable()->after('microsoft_client_id');
            $table->string('microsoft_tenant_id')->nullable()->after('microsoft_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'microsoft_login_enabled',
                'microsoft_client_id',
                'microsoft_client_secret',
                'microsoft_tenant_id',
            ]);
        });
    }
};
