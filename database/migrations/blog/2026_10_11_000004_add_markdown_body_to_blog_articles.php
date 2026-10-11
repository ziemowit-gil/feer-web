<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edytor Markdown dla Wiem FEER (osobna baza SQLite `blog`).
     *
     * `body_format` — 'html' (edytor wizualny) albo 'markdown'. Dotychczasowe wpisy
     * zostają przy 'html'; nowe w panelu domyślnie piszemy w Markdown.
     * `body_markdown` — źródło Markdown; `body` (HTML) jest z niego renderowane przy
     * zapisie, więc strona publiczna, RSS i wyszukiwarka nadal czytają `body`.
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->string('body_format', 16)->default('html');
            $table->text('body_markdown')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->dropColumn(['body_format', 'body_markdown']);
        });
    }
};
