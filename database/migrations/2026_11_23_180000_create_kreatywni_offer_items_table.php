<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Przebudowa „Mikrooferty – Kreatywni”: warsztaty oferty jako osobne rekordy (karty) — nazwa, adres podstrony (slug,
 * /warsztat/{slug}), grupa, krótki opis (karta), dłuższy opis (podstrona), cena, grafika (kolekcja mediów „image” modelu
 * KreatywniOfferItem) z opisem alternatywnym, kolejność i widoczność.
 * Istniejące wiersze tekstu kreatywni_offers.items („Tytuł | grupa | opis”) przenosimy do tabeli; sam tekst zostaje
 * jako zapas (kod bez tej tabeli czyta go jak dotąd).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kreatywni_offer_items')) {
            Schema::create('kreatywni_offer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kreatywni_offer_id')->index();
                $table->string('title');
                $table->string('slug', 160)->nullable()->index();
                $table->string('group', 120)->nullable();
                $table->text('description')->nullable();
                $table->text('details')->nullable();
                $table->string('price', 100)->nullable();
                $table->string('image_alt')->nullable();
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kreatywni_offers')) {
            return;
        }
        $now = now();
        $taken = DB::table('kreatywni_offer_items')->whereNotNull('slug')->pluck('slug')->flip()->all();
        $slug = function (string $title) use (&$taken): string {
            $base = \Illuminate\Support\Str::slug($title) ?: 'warsztat';
            $base = mb_substr($base, 0, 150);
            $candidate = $base;
            for ($i = 2; isset($taken[$candidate]); $i++) {
                $candidate = $base.'-'.$i;
            }
            $taken[$candidate] = true;

            return $candidate;
        };
        foreach (DB::table('kreatywni_offers')->get(['id', 'items']) as $offer) {
            if (DB::table('kreatywni_offer_items')->where('kreatywni_offer_id', $offer->id)->exists()) {
                continue;
            }
            $order = 0;
            foreach (preg_split('/\R/u', (string) $offer->items) ?: [] as $line) {
                $parts = array_map('trim', explode('|', $line, 3)) + [1 => '', 2 => ''];
                if ($parts[0] === '') {
                    continue;
                }
                DB::table('kreatywni_offer_items')->insert([
                    'kreatywni_offer_id' => $offer->id, 'title' => mb_substr($parts[0], 0, 255), 'slug' => $slug($parts[0]),
                    'group' => $parts[1] !== '' ? mb_substr($parts[1], 0, 120) : null,
                    'description' => $parts[2] !== '' ? $parts[2] : null,
                    'order' => ++$order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kreatywni_offer_items');
    }
};
