<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wstęp strony "O organizacji" jako zwykłe pole (zamiast edytora WYSIWYG),
     * dzięki czemu cała strona składa się z uporządkowanych pól.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('about_intro')->nullable()->after('about_motto_author');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('about_intro');
        });
    }
};
