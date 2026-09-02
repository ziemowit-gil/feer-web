<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('town')->default('Kraków');
            $table->string('type')->default('Inna forma prawna');
            $table->text('description')->nullable();
            $table->text('bio')->nullable();
            $table->string('website_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('login')->unique()->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_test')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
