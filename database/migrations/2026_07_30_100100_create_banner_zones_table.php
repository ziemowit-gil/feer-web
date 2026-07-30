<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Strefy wyświetlania bannerów — predefiniowane miejsca w serwisie. */
    public function up(): void
    {
        Schema::create('banner_zones', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('label', 128);
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('max_concurrent')->default(1);
            $table->timestamps();
        });

        DB::table('banner_zones')->insert([
            ['slug' => 'header',          'label' => 'Nagłówek (pod nawigacją)',           'description' => 'Pasek banerowy bezpośrednio pod menu głównym.',           'max_concurrent' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'footer',          'label' => 'Stopka (nad linkami)',                'description' => 'Baner w stopce, ponad linkami nawigacyjnymi.',              'max_concurrent' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'sidebar',         'label' => 'Sidebar (aktualności / projekty)',    'description' => 'Panel boczny na stronach z layoutem dwukolumnowym.',        'max_concurrent' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'article_between', 'label' => 'Między sekcjami artykułu',            'description' => 'Wstawiany automatycznie w treść artykułu.',                 'max_concurrent' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'popup',           'label' => 'Popup (wejście na stronę)',           'description' => 'Wyświetlany raz na sesję jako nakładka przy wejściu.',      'max_concurrent' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_zones');
    }
};
