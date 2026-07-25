<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Wiem FEER" blog lives in its own SQLite database.
     *
     * Mirrors the page availability controls: an article can be turned off
     * (is_disabled → "temporarily unavailable" message) or marked as
     * "under construction" (wip_mode: 'full' hides the body, 'notice' shows
     * an info banner above it). Each has an optional custom message.
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->boolean('is_disabled')->default(false)->after('is_published');
            $table->text('disabled_message')->nullable()->after('is_disabled');
            $table->string('wip_mode', 20)->nullable()->after('disabled_message');
            $table->text('wip_message')->nullable()->after('wip_mode');
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->dropColumn(['is_disabled', 'disabled_message', 'wip_mode', 'wip_message']);
        });
    }
};
