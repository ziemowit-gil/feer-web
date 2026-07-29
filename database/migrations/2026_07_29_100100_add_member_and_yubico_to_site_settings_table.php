<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Osobne logowanie do stron wewnętrznych (Microsoft 365).
            $table->boolean('member_login_enabled')->default(false)->after('microsoft_tenant_id');
            // Dozwolone domeny e-mail (po przecinku), np. „feer.org.pl". Puste = dowolne konto z tenanta.
            $table->string('member_allowed_domains')->nullable()->after('member_login_enabled');

            // Uwierzytelnianie kluczem YubiKey (Yubico OTP Validation Service v2.0).
            $table->string('yubico_client_id')->nullable()->after('member_allowed_domains');
            $table->text('yubico_secret_key')->nullable()->after('yubico_client_id');

            // Czy administratorzy muszą skonfigurować 2FA (logowanie hasłem).
            $table->boolean('two_factor_required_admins')->default(true)->after('yubico_secret_key');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'member_login_enabled',
                'member_allowed_domains',
                'yubico_client_id',
                'yubico_secret_key',
                'two_factor_required_admins',
            ]);
        });
    }
};
