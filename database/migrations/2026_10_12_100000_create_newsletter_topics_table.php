<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tematy subskrypcji newslettera edytowalne w panelu (wcześniej lista na stałe w Subscriber). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('key', 40)->unique();
            $table->string('label', 80);
            $table->string('description', 255)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('color', 7)->nullable();
            $table->json('news_category_slugs')->nullable();
            $table->json('feed_sources')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $seed = [
            ['news', 'Aktualności', 'Co nowego w fundacji — raz w miesiącu.', 'fa-newspaper', ['news'], true],
            ['events', 'Szkolenia i wydarzenia', 'Zaproszenia na szkolenia, webinary i spotkania.', 'fa-calendar-days', ['events'], false],
            ['blog', 'Blog Wiem FEER', 'Nowe artykuły z bloga.', 'fa-pen-nib', ['blog'], false],
            ['materials', 'Materiały edukacyjne', 'Nowe materiały do pobrania.', 'fa-book-open', ['materials'], false],
            ['etr', 'Treści ETR (Łatwy Odczyt)', 'Teksty łatwe do czytania i rozumienia.', 'fa-universal-access', ['news'], false],
            ['projects', 'Działania', 'Nowe działania i nabory.', 'fa-diagram-project', ['news'], false],
            ['campaigns', 'Kampanie zbiórkowe', 'Zbiórki i podsumowania wsparcia.', 'fa-hand-holding-heart', [], false],
        ];
        foreach ($seed as $i => [$key, $label, $desc, $icon, $sources, $default]) {
            DB::table('newsletter_topics')->insert([
                'key' => $key, 'label' => $label, 'description' => $desc, 'icon' => $icon, 'feed_sources' => json_encode($sources),
                'news_category_slugs' => json_encode($key === 'etr' ? ['etr', 'latwy-odczyt'] : ($key === 'projects' ? ['dzialania', 'projekty'] : [])),
                'is_active' => true, 'is_default' => $default, 'order' => $i, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_topics');
    }
};
