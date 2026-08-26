<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Włącza instytucjonalny wariant strony kontaktowej na istniejącej
     * instalacji. Domyślna wartość kolumny zostaje „classic", żeby świeże
     * instalacje weCMS dalej startowały z klasycznym układem.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'contact_layout')) {
            return;
        }

        // Tylko wiersze, które nadal mają wartość domyślną — świadomy wybór
        // innego wariantu w panelu nie zostaje nadpisany.
        DB::table('site_settings')
            ->where(function ($query) {
                $query->whereNull('contact_layout')->orWhere('contact_layout', 'classic');
            })
            ->update(['contact_layout' => 'tabs']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'contact_layout')) {
            return;
        }

        DB::table('site_settings')->where('contact_layout', 'tabs')->update(['contact_layout' => 'classic']);
    }
};
