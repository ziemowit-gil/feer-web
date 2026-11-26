<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Synchronizacja subskrybentów newslettera z Mautic (marketing.feer.org.pl):
 * ustawienia w site_settings (adres, konto API, segment) i stan synchronizacji w subscribers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_sync')) {
                $table->boolean('newsletter_mautic_sync')->default(false);
            }
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_url')) {
                $table->string('newsletter_mautic_url', 255)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_username')) {
                $table->string('newsletter_mautic_username', 255)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_password')) {
                $table->text('newsletter_mautic_password')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_segment')) {
                $table->unsignedInteger('newsletter_mautic_segment')->nullable();
            }
        });

        Schema::table('subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('subscribers', 'mautic_contact_id')) {
                $table->unsignedBigInteger('mautic_contact_id')->nullable();
            }
            if (! Schema::hasColumn('subscribers', 'mautic_synced_at')) {
                $table->timestamp('mautic_synced_at')->nullable();
            }
            if (! Schema::hasColumn('subscribers', 'mautic_error')) {
                $table->string('mautic_error', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['newsletter_mautic_sync', 'newsletter_mautic_url', 'newsletter_mautic_username', 'newsletter_mautic_password', 'newsletter_mautic_segment']);
        });
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn(['mautic_contact_id', 'mautic_synced_at', 'mautic_error']);
        });
    }
};
