<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Optional information bar shown at the top of the homepage, with an
            // optional call-to-action link and an optional visibility window.
            $table->text('homepage_banner_text')->nullable();
            $table->string('homepage_banner_link_label')->nullable();
            $table->string('homepage_banner_link_url')->nullable();
            $table->timestamp('homepage_banner_visible_from')->nullable();
            $table->timestamp('homepage_banner_visible_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'homepage_banner_text',
                'homepage_banner_link_label',
                'homepage_banner_link_url',
                'homepage_banner_visible_from',
                'homepage_banner_visible_until',
            ]);
        });
    }
};
