<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Zapisy „Powiadom mnie o terminie” na stronie szkolenia (gdy nie ma otwartej rekrutacji). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_notifications')) {
            return;
        }

        Schema::create('course_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('course_id')->index();
            $table->string('email');
            $table->timestamps();
            $table->unique(['course_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_notifications');
    }
};
