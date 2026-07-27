<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Strona typu „Wewnętrzna": dostęp po autoryzacji — hasłem (access_password,
     * hashowane) albo zalogowaniem (Microsoft 365 → konto w panelu), zależnie od
     * wybranego trybu (access_mode).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('access_mode')->nullable()->after('is_archived'); // password|microsoft
            $table->string('access_password')->nullable()->after('access_mode');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['access_mode', 'access_password']);
        });
    }
};
