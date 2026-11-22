<?php

use App\Models\KreatywniDocument;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mikro moduł „Dokumenty – Kreatywni” (szablon „kreatywni”): lista dokumentów fundacji w sekcji #dokumenty na stronie
 * „O nas”. Dokument prowadzi do pliku (kolekcja mediów „file”), strony serwisu albo adresu. Po utworzeniu tabeli
 * witrynom w szablonie „kreatywni” przenosimy dokumenty ze stron (podstron „Fundacji”, które zakłada seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kreatywni_documents')) {
            Schema::create('kreatywni_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->nullable()->index();
                $table->string('title');
                $table->string('description', 500)->nullable();
                $table->foreignId('page_id')->nullable()->index();
                $table->string('url', 500)->nullable();
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! class_exists(KreatywniDocument::class)) {
            return;
        }
        \Illuminate\Support\Once::flush();
        SiteSetting::query()->where('site_template', 'kreatywni')->get()
            ->reject(fn (SiteSetting $site) => method_exists($site, 'trashed') && $site->trashed())
            ->each(fn (SiteSetting $site) => KreatywniDocument::importFromPages($site));
    }

    public function down(): void
    {
        Schema::dropIfExists('kreatywni_documents');
    }
};
