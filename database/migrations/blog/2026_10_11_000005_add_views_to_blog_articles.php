<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Licznik wyświetleń wpisów Wiem FEER (osobna baza SQLite `blog`) — do sekcji
     * „Najpopularniejsze”. Liczony bez ciasteczek i bez adresów IP: najwyżej raz na
     * sesję i wpis, z pominięciem zalogowanych redaktorów (BlogController::countView).
     */
    protected $connection = 'blog';

    public function up(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->unsignedInteger('views')->default(0);
        });
    }

    public function down(): void
    {
        Schema::connection('blog')->table('blog_articles', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
};
