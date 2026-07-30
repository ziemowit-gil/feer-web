<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etr_contents', function (Blueprint $table) {
            $table->id();
            $table->morphs('etrable');
            $table->boolean('is_enabled')->default(false);
            $table->string('etr_title', 255)->nullable();
            $table->text('etr_summary')->nullable();
            $table->text('etr_content')->nullable();
            $table->timestamps();

            $table->unique(['etrable_type', 'etrable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etr_contents');
    }
};
