<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('microsoft_id');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('google_login_enabled')->default(false)->after('microsoft_tenant_id');
            $table->string('google_client_id')->nullable()->after('google_login_enabled');
            $table->text('google_client_secret')->nullable()->after('google_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['google_login_enabled', 'google_client_id', 'google_client_secret']);
        });
    }
};
