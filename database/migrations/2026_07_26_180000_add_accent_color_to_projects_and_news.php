<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Własny (dowolny) kolor akcentu per treść — nadpisuje preset „audience”.
        Schema::table('projects', function (Blueprint $table) {
            $table->string('accent_color')->nullable()->after('audience');
        });
        Schema::table('news', function (Blueprint $table) {
            $table->string('accent_color')->nullable()->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn('accent_color'));
        Schema::table('news', fn (Blueprint $t) => $t->dropColumn('accent_color'));
    }
};
