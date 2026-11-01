<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oddziały organizacji (np. oddział bez osobowości prawnej) na stronie kontaktowej: lista JSON z nazwą, dopiskiem
 * o statusie, adresem, e-mailem, telefonem, godzinami i notatką.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'contact_branches')) {
                $table->json('contact_branches')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'contact_branches_title')) {
                $table->string('contact_branches_title', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['contact_branches', 'contact_branches_title'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
