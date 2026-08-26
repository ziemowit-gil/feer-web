<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Włącza substyl „Urzędowy" nagłówka na instalacjach, które nigdy nie
     * wybrały układu świadomie — czyli mają NULL albo starą wartość „default"
     * spoza listy HEADER_LAYOUTS (normalizowaną do „classic").
     *
     * Świadomy wybór z panelu („classic", „brand_bar", „wide_mission"…)
     * zostaje nietknięty, a układ dalej zmienia się w Ustawieniach → Nagłówek.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'header_layout')) {
            return;
        }

        DB::table('site_settings')
            ->where(function ($query) {
                $query->whereNull('header_layout')->orWhere('header_layout', 'default');
            })
            ->update(['header_layout' => 'office_bar']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'header_layout')) {
            return;
        }

        DB::table('site_settings')->where('header_layout', 'office_bar')->update(['header_layout' => 'default']);
    }
};
