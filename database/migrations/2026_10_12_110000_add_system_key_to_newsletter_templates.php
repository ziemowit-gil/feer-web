<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Szablony systemowe (np. mail potwierdzający zapis) edytowalne w Mosaico. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_templates', function (Blueprint $table) {
            $table->string('system_key', 40)->nullable()->unique()->after('kind');
            $table->string('subject', 255)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_templates', function (Blueprint $table) {
            $table->dropColumn(['system_key', 'subject']);
        });
    }
};
