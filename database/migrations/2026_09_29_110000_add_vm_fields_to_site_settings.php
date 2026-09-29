<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalna treść szablonu "vm" (wzorowanego na fundacjavismaior.pl):
 * blok powitalny z przyciskami, sekcja „Wiedza" (strona i jej podstrony),
 * tekst zachęty do newslettera, podpis logo partnera w nagłówku i nota
 * o finansowaniu w stopce.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('vm_intro_heading')->nullable()->after('ngo_3_stats');
            $table->text('vm_intro_text')->nullable()->after('vm_intro_heading');
            $table->json('vm_intro_buttons')->nullable()->after('vm_intro_text');
            $table->unsignedBigInteger('vm_knowledge_page_id')->nullable()->after('vm_intro_buttons');
            $table->text('vm_newsletter_text')->nullable()->after('vm_knowledge_page_id');
            $table->string('vm_header_badge_alt')->nullable()->after('vm_newsletter_text');
            $table->text('vm_footer_note')->nullable()->after('vm_header_badge_alt');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'vm_intro_heading', 'vm_intro_text', 'vm_intro_buttons', 'vm_knowledge_page_id',
                'vm_newsletter_text', 'vm_header_badge_alt', 'vm_footer_note',
            ]);
        });
    }
};
