<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sklep_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sklep_order_id')->constrained('sklep_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('educational_material_id');
            $table->string('title');
            $table->unsignedInteger('unit_price_grosze');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sklep_order_items');
    }
};
