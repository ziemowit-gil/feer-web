<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wersja robocza opublikowanej strony (jak Workspaces w TYPO3): zmiany przygotowane w edytorze blokowym czekają
 * tutaj, a odwiedzający widzą stronę bez zmian, dopóki ktoś z prawem akceptacji ich nie opublikuje.
 * Jedna wersja robocza na stronę.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_drafts')) {
            return;
        }
        Schema::create('page_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('meta_description')->nullable();
            $table->json('blocks')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_drafts');
    }
};
