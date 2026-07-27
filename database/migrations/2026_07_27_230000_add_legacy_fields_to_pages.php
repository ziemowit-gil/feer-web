<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pola typu „Prezentacja tego, co było": nazwa podmiotu poprzedzającego FEER
     * oraz wstęp. Historia/działalność idą w polu content (WYSIWYG) i galerii.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('legacy_name')->nullable()->after('hub_links');
            $table->text('legacy_intro')->nullable()->after('legacy_name');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['legacy_name', 'legacy_intro']);
        });
    }
};
