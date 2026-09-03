<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Nullable — brak wartości oznacza "dziedzicz z .env" (config/przelewy24.php),
            // patrz SiteSetting::przelewy24Config(). Pozwala włączyć integrację z panelu,
            // bez dostępu do serwera.
            $table->boolean('przelewy24_sandbox')->nullable()->after('yubico_secret_key');
            $table->string('przelewy24_merchant_id')->nullable()->after('przelewy24_sandbox');
            $table->string('przelewy24_pos_id')->nullable()->after('przelewy24_merchant_id');
            $table->text('przelewy24_crc')->nullable()->after('przelewy24_pos_id');
            $table->text('przelewy24_api_key')->nullable()->after('przelewy24_crc');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'przelewy24_sandbox', 'przelewy24_merchant_id', 'przelewy24_pos_id',
                'przelewy24_crc', 'przelewy24_api_key',
            ]);
        });
    }
};
