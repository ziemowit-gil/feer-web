<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sklep_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('educational_material_id');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_id')->unique();
            $table->unsignedBigInteger('p24_order_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount_grosze');
            $table->string('currency', 3)->default('PLN');
            $table->string('access_token', 64)->unique();
            $table->timestamp('access_delivered_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sklep_orders');
    }
};
