<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabele treści, w których zapamiętujemy autora (created_by) — podstawa zakresu „tylko własne wpisy". */
    private array $tables = ['news', 'projects', 'pages', 'events'];

    public function up(): void
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->boolean('own_content_only')->default(false);
            $table->json('project_category_ids')->nullable(); // null/[] = wszystkie kategorie projektów
        });

        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('created_by'));
        }
        Schema::table('user_groups', fn (Blueprint $table) => $table->dropColumn(['own_content_only', 'project_category_ids']));
    }
};
