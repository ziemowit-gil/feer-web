<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Odnośnik do FAQ na stronie „O organizacji" prowadzi zawsze do /faq, więc
     * zamiast wskazywać stronę wystarczy przełącznik widoczności sekcji.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('about_faq_visible')->default(false)->after('about_press');
        });

        if (Schema::hasColumn('pages', 'about_faq_page_id')) {
            // Zachowaj włączenie: gdzie był ustawiony cel, tam pokaż odnośnik.
            \Illuminate\Support\Facades\DB::table('pages')->whereNotNull('about_faq_page_id')->update(['about_faq_visible' => true]);

            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('about_faq_page_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedBigInteger('about_faq_page_id')->nullable()->after('about_press');
            $table->dropColumn('about_faq_visible');
        });
    }
};
