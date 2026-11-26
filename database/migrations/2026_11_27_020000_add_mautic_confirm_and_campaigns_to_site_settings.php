<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter z Mautic, cz. 2: e-mail potwierdzający zapis wysyła Mautic (ID szablonu e-maila w Mautic)
 * i przełącznik „kampanie wysyłam w Mautic” (CMS chowa tworzenie i wysyłkę kampanii).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_confirm_email')) {
                $table->unsignedInteger('newsletter_mautic_confirm_email')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'newsletter_mautic_campaigns')) {
                $table->boolean('newsletter_mautic_campaigns')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['newsletter_mautic_confirm_email', 'newsletter_mautic_campaigns']);
        });
    }
};
