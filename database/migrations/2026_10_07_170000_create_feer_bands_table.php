<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feer_bands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('button_label', 80)->nullable();
            $table->string('button_url')->nullable();
            $table->string('button2_label', 80)->nullable();
            $table->string('button2_url')->nullable();
            $table->string('style', 20)->default('brand');          // brand | dark | light
            $table->string('placement', 30)->default('after_news'); // miejsce na stronie głównej albo „shortcode"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feer_bands');
    }
};
