<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();   // migawka nazwy autora zdarzenia
            $table->string('event');                    // created | updated | deleted
            $table->string('subject_type')->nullable(); // np. News, Page, Project
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable(); // migawka tytułu/nazwy
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
