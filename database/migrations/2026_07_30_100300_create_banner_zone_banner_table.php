<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabela pośrednia: przypisanie bannerów do stref wyświetlania z priorytetem. */
    public function up(): void
    {
        Schema::create('banner_zone_banner', function (Blueprint $table) {
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banner_zone_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();
            $table->primary(['banner_id', 'banner_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_zone_banner');
    }
};
