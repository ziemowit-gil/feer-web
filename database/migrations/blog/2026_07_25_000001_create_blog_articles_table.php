<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Wiem FEER" blog lives in its own SQLite database.
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->create('blog_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author_name')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->dropIfExists('blog_articles');
    }
};
