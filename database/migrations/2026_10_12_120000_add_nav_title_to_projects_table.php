<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Krótka nazwa działania do nagłówka menu bocznego (długi tytuł zajmował kilka linijek). */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('nav_title', 80)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('nav_title');
        });
    }
};
