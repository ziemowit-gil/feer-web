<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Slajd widoczny od–do (np. nabór, wydarzenie) — poza terminem nie pokazuje się na stronie. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            if (! Schema::hasColumn('hero_slides', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('hero_slides', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
