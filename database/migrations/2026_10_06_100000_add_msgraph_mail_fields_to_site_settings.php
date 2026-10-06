<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wysyłka poczty przez Microsoft Graph (Microsoft 365, uprawnienie aplikacyjne
 * Mail.Send): dane aplikacji Azure, skrzynka nadawcza oraz przełącznik
 * „powiadomienia z formularzy domyślnie przez Graph”.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('msgraph_tenant_id', 100)->nullable();
            $table->string('msgraph_client_id', 100)->nullable();
            $table->text('msgraph_client_secret')->nullable();
            $table->string('msgraph_sender')->nullable();
            $table->boolean('msgraph_save_to_sent')->default(true);
            $table->boolean('forms_mail_via_msgraph')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'msgraph_tenant_id', 'msgraph_client_id', 'msgraph_client_secret',
                'msgraph_sender', 'msgraph_save_to_sent', 'forms_mail_via_msgraph',
            ]);
        });
    }
};
