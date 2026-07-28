<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->boolean('is_published')->default(true);

            // Hero
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title');                 // H1
            $table->text('hero_lead')->nullable();
            $table->string('hero_cta_label')->default('Zarejestruj się');
            $table->string('hero_image_url')->nullable();
            $table->dateTime('event_start')->nullable();
            $table->string('event_location')->nullable();

            // Dynamiczne sekcje (edytowalne w panelu)
            $table->json('speakers')->nullable();         // [{name, role, bio, photo}]
            $table->json('benefits')->nullable();         // [{icon, title, text}]
            $table->json('agenda')->nullable();           // [{time, title, desc}]
            $table->json('section_order')->nullable();    // ['speakers','benefits','agenda']

            // Formularz
            $table->string('form_title')->default('Zapisz się na webinar');
            $table->text('form_intro')->nullable();
            $table->text('form_success')->nullable();
            $table->string('form_consent_label')->nullable();

            $table->timestamps();
        });

        Schema::create('webinar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('consent')->default(false);
            $table->boolean('forwarded')->default(false); // przekazano do zewn. API
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_registrations');
        Schema::dropIfExists('landing_pages');
    }
};
