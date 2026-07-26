<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wybrane loga partnerów pokazywane w sekcji „Nasi partnerzy” na stronie
        // typu „O organizacji”. Lista ID partnerów w wybranej kolejności.
        Schema::table('pages', function (Blueprint $table) {
            $table->json('about_partner_ids')->nullable()->after('about_section_order');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('about_partner_ids'));
    }
};
