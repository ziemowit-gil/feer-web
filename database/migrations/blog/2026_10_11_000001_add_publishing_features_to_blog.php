<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Wiem FEER" blog lives in its own SQLite database.
     *
     * Rozbudowa bloga: zdjęcie wyróżniające (ścieżka na dysku `public` + tekst
     * alternatywny albo oznaczenie „dekoracyjne"), wpis wyróżniony, krótka nota
     * o autorze, pola SEO, kategorie (jedna na wpis) i tagi (wiele), a w
     * komentarzach — odpowiedzi redakcji (parent_id + is_staff_reply).
     *
     * Kategoria wpisu to zwykła kolumna z indeksem (bez klucza obcego — SQLite
     * nie dodaje go przez ALTER TABLE); usuwanie kategorii czyści ją w kontrolerze.
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('blog')->create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::connection('blog')->create('blog_article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('image_decorative')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->text('author_bio')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
        });

        Schema::connection('blog')->table('blog_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->boolean('is_staff_reply')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->table('blog_comments', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'is_staff_reply', 'user_id']);
        });

        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropColumn([
                'category_id', 'image_path', 'image_alt', 'image_decorative', 'is_featured',
                'author_bio', 'meta_title', 'meta_description',
            ]);
        });

        Schema::connection('blog')->dropIfExists('blog_article_tag');
        Schema::connection('blog')->dropIfExists('blog_tags');
        Schema::connection('blog')->dropIfExists('blog_categories');
    }
};
