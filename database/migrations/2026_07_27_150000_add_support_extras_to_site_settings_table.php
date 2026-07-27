<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dodatki strony „Wesprzyj nas": logotypy zaufania (partnerzy), social proof
     * (cytat) oraz obsługa zbiórki na cele FEER (cel + zebrana kwota + pasek).
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('support_show_partners')->default(false)->after('support_wplacam_url');

            $table->text('support_testimonial_quote')->nullable()->after('support_show_partners');
            $table->string('support_testimonial_author')->nullable()->after('support_testimonial_quote');
            $table->string('support_testimonial_role')->nullable()->after('support_testimonial_author');

            $table->string('support_fundraiser_title')->nullable()->after('support_testimonial_role');
            $table->text('support_fundraiser_text')->nullable()->after('support_fundraiser_title');
            $table->unsignedInteger('support_fundraiser_goal')->nullable()->after('support_fundraiser_text');
            $table->unsignedInteger('support_fundraiser_raised')->nullable()->after('support_fundraiser_goal');
            $table->string('support_fundraiser_url')->nullable()->after('support_fundraiser_raised');
            $table->string('support_fundraiser_cta_label')->nullable()->after('support_fundraiser_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'support_show_partners',
                'support_testimonial_quote', 'support_testimonial_author', 'support_testimonial_role',
                'support_fundraiser_title', 'support_fundraiser_text', 'support_fundraiser_goal',
                'support_fundraiser_raised', 'support_fundraiser_url', 'support_fundraiser_cta_label',
            ]);
        });
    }
};
