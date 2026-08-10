<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('lead', 300);

            $table->string('job_type');            // pełny etat, pół etatu, b2b, umowa o dzieło, praktyka
            $table->string('mode');                // stacjonarnie|zdalnie|hybrydowo
            $table->string('location')->nullable();
            $table->string('salary_range')->nullable(); // tekstowe widełki, np. „3500–5000 PLN brutto/mies."
            $table->string('hourly_rate')->nullable();   // stawka godzinowa dla zleceń, np. „35–45 PLN/h"

            // Pola specyficzne dla UOP / praktyki
            $table->string('contract_duration_type')->nullable(); // okreslony|nieokreslony
            $table->string('contract_duration')->nullable();      // np. „6 miesięcy", „1 rok"
            $table->date('start_date')->nullable();                // proponowana data rozpoczęcia

            $table->json('duties');                // lista obowiązków
            $table->json('requirements');          // lista wymagań
            $table->json('benefits')->nullable();  // lista benefitów (opcjonalna)

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('application_url')->nullable();
            $table->string('application_cta_label')->default('Aplikuj');

            $table->string('audience')->default('brand');
            $table->boolean('is_published')->default(false);
            $table->date('closes_at')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
