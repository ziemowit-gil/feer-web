<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import subskrybentów newslettera (plik CSV/JSON, eksport MailerLite, API MailerLite):
 * tabela przebiegów importu z podglądem, mapowaniem, postępem i raportem oraz
 * zaszyfrowany token API MailerLite w ustawieniach witryny.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('source', 20);
            $table->string('status', 20)->default('preview');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->json('columns')->nullable();
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->json('summary')->nullable();
            $table->text('cursor')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('total')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'newsletter_mailerlite_token')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->text('newsletter_mailerlite_token')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_imports');

        if (Schema::hasColumn('site_settings', 'newsletter_mailerlite_token')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('newsletter_mailerlite_token');
            });
        }
    }
};
