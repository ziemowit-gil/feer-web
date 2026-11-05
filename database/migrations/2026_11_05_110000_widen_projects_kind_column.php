<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** `kind` miało 12 znaków; „foundation_activity” (19) nie mieściło się w MySQL. */
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'kind')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('kind', 32)->default('project')->change();
            });
        }
    }

    public function down(): void
    {
        // Celowo bez zawężania: skróciłoby wartości „foundation_activity”.
    }
};
