<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Poziom dostępu dla dowolnej strony: null/inherit = jak strona nadrzędna (dla korzenia: publiczna).
            $table->string('access_level', 12)->nullable();
            $table->json('access_group_ids')->nullable(); // dla poziomu „groups": identyfikatory grup użytkowników panelu
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn(['access_level', 'access_group_ids']));
    }
};
