<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL 8 nie przyjmuje zwykłej wartości domyślnej dla JSON — tylko wyrażenie w nawiasach (MariaDB i SQLite: bez zmian).
        $jsonDefault = fn (string $v) => in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true) ? new Expression("('{$v}')") : $v;

        Schema::create('form_submissions', function (Blueprint $table) use ($jsonDefault) {
            $table->id();
            $table->foreignId('form_definition_id')->constrained()->cascadeOnDelete();
            $table->json('data')->default($jsonDefault('{}'));
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
