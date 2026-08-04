<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('facilitator_website')->nullable()->after('facilitator_bio');
            $table->string('facilitator_linkedin')->nullable()->after('facilitator_website');
            $table->string('facilitator_facebook')->nullable()->after('facilitator_linkedin');
            $table->string('facilitator_instagram')->nullable()->after('facilitator_facebook');
            $table->string('facilitator_twitter')->nullable()->after('facilitator_instagram');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'facilitator_website',
                'facilitator_linkedin',
                'facilitator_facebook',
                'facilitator_instagram',
                'facilitator_twitter',
            ]);
        });
    }
};
