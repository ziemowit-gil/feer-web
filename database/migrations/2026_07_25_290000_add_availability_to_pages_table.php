<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds two independent availability controls to pages:
     *  - is_disabled: the page is turned off and shows a friendly
     *    "temporarily unavailable" message instead of its content.
     *  - wip_mode: the page is "under construction" — either a full-screen
     *    notice ('full', hides content) or an info banner above the content
     *    ('notice', content stays visible). Null means the page is not WIP.
     * Each mode has an optional custom message; a sensible default is used
     * when it is left empty.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_disabled')->default(false)->after('is_published');
            $table->text('disabled_message')->nullable()->after('is_disabled');
            $table->string('wip_mode', 20)->nullable()->after('disabled_message');
            $table->text('wip_message')->nullable()->after('wip_mode');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['is_disabled', 'disabled_message', 'wip_mode', 'wip_message']);
        });
    }
};
