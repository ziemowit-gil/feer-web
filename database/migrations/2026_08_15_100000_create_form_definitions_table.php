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

        Schema::create('form_definitions', function (Blueprint $table) use ($jsonDefault) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('fields')->default($jsonDefault('[]'));
            $table->json('settings')->default($jsonDefault('{}'));
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_definitions');
    }
};
