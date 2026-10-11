<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Wiem FEER" blog lives in its own SQLite database.
     *
     * Autorzy wpisów: profil (rola, nota, zdjęcie, linki, e-mail pokazywany
     * tylko na życzenie), opcjonalne powiązanie z użytkownikiem CMS (`user_id`
     * — zwykła liczba, bez klucza obcego między bazami). Wpis ma autora
     * głównego (`author_id`) i współautorów (tabela łącząca z kolejnością).
     * Dotychczasowe pole `author_name` zostaje jako zapas dla starych wpisów.
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->create('blog_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_alt')->nullable();
            $table->string('email')->nullable();
            $table->boolean('show_email')->default(false);
            $table->json('links')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection('blog')->create('blog_article_author', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('blog_authors')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['article_id', 'author_id']);
        });

        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->dropIndex(['author_id']);
            $table->dropColumn('author_id');
        });

        Schema::connection('blog')->dropIfExists('blog_article_author');
        Schema::connection('blog')->dropIfExists('blog_authors');
    }
};
