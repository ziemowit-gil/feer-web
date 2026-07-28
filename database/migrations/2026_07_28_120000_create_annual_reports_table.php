<?php

use App\Models\NavItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_reports', function (Blueprint $table) {
            $table->id();
            // Jeden wiersz = jeden rok sprawozdawczy.
            $table->unsignedSmallInteger('year')->unique();

            // Status i (dla powodu własnego) uzasadnienie dla każdego z dwóch
            // sprawozdań. Sam plik PDF żyje w kolekcji mediów Spatie.
            $table->string('substantive_status')->default('not_yet');
            $table->text('substantive_reason')->nullable();
            $table->string('financial_status')->default('not_yet');
            $table->text('financial_reason')->nullable();

            // Ukrycie całego wiersza (roku) na publicznej stronie bez usuwania danych.
            $table->boolean('is_published')->default(true);

            $table->timestamps();
        });

        // Powiąż istniejącą pozycję menu „Sprawozdania" z modułem, aby znikała
        // z nawigacji po wyłączeniu modułu (mechanizm modułów pozycji menu).
        if (Schema::hasTable('nav_items')) {
            NavItem::where('url', '/sprawozdania')->update(['module' => 'reports']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            NavItem::where('url', '/sprawozdania')->where('module', 'reports')->update(['module' => null]);
        }

        Schema::dropIfExists('annual_reports');
    }
};
