<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Strona „dopiero tworzymy” dla witryny (osobno od trybu konserwacji). */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'under_construction')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->boolean('under_construction')->default(false)->after('maintenance_message');
                $table->text('under_construction_message')->nullable()->after('under_construction');
            });
        }

        // Sub-witryny na szablonie test2 są dopiero budowane.
        DB::table('site_settings')->whereNotNull('parent_site_id')->where('site_template', 'test2')->update(['under_construction' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'under_construction')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['under_construction', 'under_construction_message']));
        }
    }
};
