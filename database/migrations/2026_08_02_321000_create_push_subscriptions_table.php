<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela subskrypcji Web Push — przechowuje endpoint i klucze szyfrowania
 * dla każdego przeglądarki/urządzenia, które wyraziło zgodę na powiadomienia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint')->unique();
            $table->text('p256dh_key');
            $table->string('auth_token', 255);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
