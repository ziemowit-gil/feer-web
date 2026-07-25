<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable copy for every text element of the /wsparcie page. All nullable:
     * a null column falls back to the built-in default in SiteSetting::SUPPORT_DEFAULTS.
     */
    private array $columns = [
        // Hero
        'support_hero_badge',
        'support_hero_title',
        'support_hero_subtitle',
        'support_hero_cta_label',
        // "Dlaczego warto nas wspierać"
        'support_benefits_title',
        'support_benefits_subtitle',
        'support_benefit1_icon',
        'support_benefit1_title',
        'support_benefit1_text',
        'support_benefit2_icon',
        'support_benefit2_title',
        'support_benefit2_text',
        'support_benefit3_icon',
        'support_benefit3_title',
        'support_benefit3_text',
        // "Jak możesz pomóc"
        'support_methods_title',
        'support_method1_title',
        'support_method1_account_label',
        'support_method1_tax_label',
        'support_method2_title',
        'support_method2_text',
        'support_method2_cta_label',
        'support_method3_title',
        'support_method3_text',
        'support_method3_cta_label',
        // Closing box
        'support_outro_title',
        'support_outro_subtitle',
    ];

    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                $table->text($column)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn($this->columns);
        });
    }
};
