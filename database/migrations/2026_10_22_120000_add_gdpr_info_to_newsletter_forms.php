<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Klauzula informacyjna RODO (art. 13) i kontakt do IOD edytowalne przy formularzu zapisu na newsletter. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_forms', 'gdpr_info')) {
                $table->text('gdpr_info')->nullable()->after('consent_text');
            }
            if (! Schema::hasColumn('newsletter_forms', 'gdpr_dpo')) {
                $table->string('gdpr_dpo', 255)->nullable()->after('consent_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            foreach (['gdpr_info', 'gdpr_dpo'] as $column) {
                if (Schema::hasColumn('newsletter_forms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
