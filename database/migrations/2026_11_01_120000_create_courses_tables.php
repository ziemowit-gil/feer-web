<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł „Szkolenia i zajęcia”: oferta (courses), terminy (course_editions) i zapisy uczestników (course_registrations).
 * Oferta należy do witryny (site_id), terminy i zapisy dziedziczą ją przez szkolenie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('site_id')->nullable()->index();
            $t->string('title');
            $t->string('slug');
            $t->string('lead', 500)->nullable();
            $t->longText('description')->nullable();
            $t->string('category', 60)->nullable()->index();
            $t->string('format', 20)->default('stationary');
            $t->decimal('price', 8, 2)->nullable();
            $t->string('status', 20)->default('draft')->index();
            $t->boolean('is_featured')->default(false);
            $t->unsignedInteger('order')->default(0);
            $t->timestamps();

            $t->unique(['site_id', 'slug']);
        });

        Schema::create('course_editions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->string('schedule')->nullable();
            $t->string('location')->nullable();
            $t->string('meeting_url')->nullable();
            $t->string('instructor')->nullable();
            $t->unsignedInteger('capacity')->default(10);
            $t->string('enrollment_status', 20)->default('open');
            $t->timestamps();

            $t->index(['course_id', 'starts_on']);
        });

        Schema::create('course_registrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('edition_id')->constrained('course_editions')->cascadeOnDelete();
            $t->string('first_name', 100);
            $t->string('last_name', 100);
            $t->string('email');
            $t->string('phone', 40)->nullable();
            $t->text('accessibility_needs')->nullable();
            $t->string('status', 20)->default('pending')->index();
            $t->string('payment_reference', 20)->unique();
            $t->string('source', 20)->default('web');
            $t->timestamps();

            $t->index(['edition_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
        Schema::dropIfExists('course_editions');
        Schema::dropIfExists('courses');
    }
};
