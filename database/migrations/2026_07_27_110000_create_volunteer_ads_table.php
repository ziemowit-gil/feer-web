<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ogłoszenia o wolontariacie zbudowane wokół „zasady 6 pytań KCW": każde
     * pytanie to osobne pole, co wymusza kompletne, konkretne ogłoszenie.
     * Zgłoszenia zbieramy zewnętrznym formularzem — stąd osobne, edytowalne
     * pole application_url (link do Typeform / Google Forms itp.).
     */
    public function up(): void
    {
        Schema::create('volunteer_ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('lead', 300);

            // Zasada 6 pytań (każde pytanie = osobne pole)
            $table->text('q_beneficiaries');          // 1. Komu pomagamy?
            $table->json('q_tasks');                   // 2. Na czym polega? [lista]
            $table->string('q_mode');                  // 3a. stacjonarnie|zdalnie|hybrydowo
            $table->string('q_location')->nullable();  // 3b. gdzie
            $table->string('q_schedule');              // 3c. kiedy / harmonogram
            $table->string('q_time_commitment');       // 4. ile czasu
            $table->json('q_benefits');                // 5. co zyska wolontariusz? [lista]
            $table->text('q_how_to_apply');            // 6. jak się zgłosić?

            // Link do zewnętrznego formularza zgłoszeniowego (osobne pole)
            $table->string('application_url')->nullable();
            $table->string('application_cta_label')->default('Zgłoś się');
            $table->string('contact_email')->nullable();

            $table->string('audience')->default('brand');
            $table->boolean('is_published')->default(false);
            $table->date('closes_at')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_ads');
    }
};
