<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('federation_show_org_spotlight')->default(false)->after('federation_colorful_nav_items');
            $table->boolean('federation_show_members_banner')->default(true)->after('federation_show_org_spotlight');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['federation_show_org_spotlight', 'federation_show_members_banner']);
        });
    }
};
