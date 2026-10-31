<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł „Jak wesprzeć w trzech krokach” na stronie /wsparcie: edytowalne teksty (puste = wartość domyślna z
 * SiteSetting::SUPPORT_DEFAULTS) i przełącznik ukrycia całej sekcji.
 */
return new class extends Migration
{
    private array $texts = [
        'support_steps_title', 'support_steps_subtitle',
        'support_step1_title', 'support_step1_text',
        'support_step2_title', 'support_step2_text',
        'support_step3_title', 'support_step3_text',
    ];

    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach ($this->texts as $column) {
                if (! Schema::hasColumn('site_settings', $column)) {
                    $table->text($column)->nullable();
                }
            }
            if (! Schema::hasColumn('site_settings', 'support_steps_hidden')) {
                $table->boolean('support_steps_hidden')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(array_merge($this->texts, ['support_steps_hidden']));
        });
    }
};
