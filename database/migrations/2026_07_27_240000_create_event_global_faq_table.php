<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Powiązanie wydarzeń z pytaniami z globalnego FAQ (wiele-do-wielu). Dzięki
     * temu to samo pytanie z /faq można dopiąć do wielu szkoleń bez duplikacji.
     */
    public function up(): void
    {
        Schema::create('event_global_faq', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'faq_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_global_faq');
    }
};
