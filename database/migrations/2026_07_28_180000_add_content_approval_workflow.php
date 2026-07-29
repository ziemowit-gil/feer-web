<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uprawnienie grupy: może zatwierdzać i publikować treść (moderator/akceptant).
        Schema::table('user_groups', function (Blueprint $table) {
            $table->boolean('can_approve')->default(false)->after('modules');
        });

        // Stan „oczekuje na zatwierdzenie" na treściach objętych obiegiem akceptacji.
        foreach (['news', 'pages', 'projects'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('pending_approval')->default(false);
                $t->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_groups', fn (Blueprint $t) => $t->dropColumn('can_approve'));

        foreach (['news', 'pages', 'projects'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('submitted_by_id');
                $t->dropColumn('pending_approval');
            });
        }
    }
};
