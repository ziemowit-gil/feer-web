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
            $table->text('contact_box_text')->nullable();
            $table->string('contact_box_link_label')->nullable();
            $table->string('contact_box_link_url')->nullable();
            $table->timestamp('contact_box_visible_from')->nullable();
            $table->timestamp('contact_box_visible_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_box_text',
                'contact_box_link_label',
                'contact_box_link_url',
                'contact_box_visible_from',
                'contact_box_visible_until',
            ]);
        });
    }
};
