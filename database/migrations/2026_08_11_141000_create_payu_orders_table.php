<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payu_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payu_order_id')->unique();
            $table->string('plan_slug')->nullable();
            $table->unsignedBigInteger('podcast_id')->nullable();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->string('status', 32)->default('PENDING');
            $table->unsignedInteger('amount_grosze');
            $table->string('currency', 3)->default('PLN');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payu_orders');
    }
};
