<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rozbudowa biblioteki plików:
 *  - media_versions — poprzednie wersje pliku po podmianie (adres pliku się nie zmienia),
 *  - media.sha1 — skrót treści do wykrywania duplikatów (liczony leniwie),
 *  - media.a11y_report / a11y_checked_at — wynik sprawdzenia dostępności dokumentu (PDF, DOCX),
 *  - media_folder_user_group — grupy użytkowników z dostępem do folderu (brak wpisów = folder dla wszystkich).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path'); // na dysku „local”, poza katalogiem publicznym
            $table->foreignId('replaced_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->string('sha1', 40)->nullable()->index();
            $table->json('a11y_report')->nullable();
            $table->timestamp('a11y_checked_at')->nullable();
        });

        Schema::create('media_folder_user_group', function (Blueprint $table) {
            $table->foreignId('media_folder_id')->constrained('media_folders')->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained('user_groups')->cascadeOnDelete();
            $table->primary(['media_folder_id', 'user_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folder_user_group');
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['sha1']);
            $table->dropColumn(['sha1', 'a11y_report', 'a11y_checked_at']);
        });
        Schema::dropIfExists('media_versions');
    }
};
