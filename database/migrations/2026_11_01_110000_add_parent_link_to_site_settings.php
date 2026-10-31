<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link „Powrót do portalu Fundacji” w górnym pasku witryny w sieci: napis i adres edytowalne w jej ustawieniach
 * (puste = napis domyślny i adres witryny głównej).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'parent_link_label')) {
                $table->string('parent_link_label', 80)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'parent_link_url')) {
                $table->string('parent_link_url', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['parent_link_label', 'parent_link_url'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
