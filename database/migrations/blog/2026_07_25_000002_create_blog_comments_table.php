<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->string('author_name');
            $table->string('email')->nullable();
            $table->text('body');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['article_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->dropIfExists('blog_comments');
    }
};
