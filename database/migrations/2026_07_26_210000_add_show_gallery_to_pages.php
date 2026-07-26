<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Przełącznik pokazywania galerii zdjęć na stronie (dla podstron innych
        // niż „O organizacji”, która ma własną, wbudowaną galerię).
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_gallery')->default(false)->after('about_partner_ids');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('show_gallery'));
    }
};
