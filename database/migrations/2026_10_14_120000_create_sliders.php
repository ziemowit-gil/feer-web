<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wiele sliderów: każdy ma nazwę, miejsce wyświetlania (strona główna, blog, wybrane strony)
 * i własne slajdy (hero_slides.slider_id). Dotychczasowe slajdy trafiają do slidera „Strona główna”.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 120);
            $table->boolean('is_home')->default(false);
            $table->boolean('show_on_blog')->default(false);
            $table->unsignedSmallInteger('duration')->nullable(); // sekundy na slajd (domyślnie 6)
            $table->timestamps();
        });

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->foreignId('slider_id')->nullable()->after('id')->constrained('sliders')->nullOnDelete();
        });

        // Istniejące slajdy → slider „Strona główna” dla każdej witryny, która ma slajdy.
        $sites = DB::table('hero_slides')->select('site_id')->distinct()->pluck('site_id');
        foreach ($sites as $siteId) {
            $id = DB::table('sliders')->insertGetId([
                'site_id' => $siteId, 'name' => 'Strona główna', 'is_home' => true, 'show_on_blog' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('hero_slides')->where(fn ($q) => $siteId === null ? $q->whereNull('site_id') : $q->where('site_id', $siteId))
                ->update(['slider_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('hero_slides', fn (Blueprint $table) => $table->dropConstrainedForeignId('slider_id'));
        Schema::dropIfExists('sliders');
    }
};
