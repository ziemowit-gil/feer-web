<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konfiguracja bramki pocztowej ustawiana z panelu (nadpisuje .env w runtime).
     * Etap 1: tryb "default" (dziedzicz z .env) i "smtp". Kolumny dla Microsoft
     * Graph (Azure) dokładamy w osobnym kroku, gdy będą dane rejestracji Azure.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('mail_transport')->default('default'); // default | smtp
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();       // cast: encrypted
            $table->string('mail_encryption')->nullable();   // tls | ssl | null
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_transport', 'mail_from_address', 'mail_from_name',
                'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption',
            ]);
        });
    }
};
