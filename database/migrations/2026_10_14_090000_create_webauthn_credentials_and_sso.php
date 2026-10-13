<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logowanie bez hasła kluczem dostępu (passkey / WebAuthn — YubiKey, odcisk palca, Windows Hello, telefon)
 * oraz SSO przez OpenID Connect dla organizacji z własnym dostawcą tożsamości (Keycloak, Okta, Entra, Google Workspace).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('credential_id', 512)->unique(); // base64url
            $table->text('public_key');                       // PEM (ES256/RS256) albo surowy klucz Ed25519 (base64)
            $table->smallInteger('algorithm');                 // COSE: -7 ES256, -257 RS256, -8 EdDSA
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->json('transports')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'oidc_sub')) {
                $table->string('oidc_sub')->nullable()->index();
            }
        });

        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['sso_enabled' => 'boolean', 'sso_only_login' => 'boolean', 'passkey_login_enabled' => 'boolean'] as $col => $type) {
                if (! Schema::hasColumn('site_settings', $col)) {
                    $table->boolean($col)->default($col === 'passkey_login_enabled');
                }
            }
            foreach (['sso_name' => 120, 'sso_issuer' => 255, 'sso_client_id' => 255, 'sso_scopes' => 255] as $col => $len) {
                if (! Schema::hasColumn('site_settings', $col)) {
                    $table->string($col, $len)->nullable();
                }
            }
            if (! Schema::hasColumn('site_settings', 'sso_client_secret')) {
                $table->text('sso_client_secret')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('oidc_sub'));
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn([
            'sso_enabled', 'sso_only_login', 'passkey_login_enabled', 'sso_name', 'sso_issuer', 'sso_client_id', 'sso_scopes', 'sso_client_secret',
        ]));
    }
};
