<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolejna metoda wsparcia na /wsparcie: portal wplacam.ngo.pl (osobny,
     * edytowalny link) wraz z edytowalnym opisem karty.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('support_wplacam_url')->nullable()->after('support_buycoffee_url');
            $table->string('support_method4_title')->nullable()->after('support_wplacam_url');
            $table->string('support_method4_text')->nullable()->after('support_method4_title');
            $table->string('support_method4_cta_label')->nullable()->after('support_method4_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['support_wplacam_url', 'support_method4_title', 'support_method4_text', 'support_method4_cta_label']);
        });
    }
};
