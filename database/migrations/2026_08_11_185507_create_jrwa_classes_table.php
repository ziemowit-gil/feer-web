<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jrwa_classes', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('name', 500);
            // A, B3, B5, B10, B50, BE10, Bc
            $table->string('category', 10)->default('B10');
            $table->text('notes')->nullable();
            // 0 = folder/group, 1 = leaf (where documents are filed), 2 = withdrawn
            $table->tinyInteger('flag')->default(0);
            // self-referencing; null for top-level classes
            $table->string('parent_symbol', 20)->nullable()->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jrwa_classes');
    }
};
