<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('person_phone', 60)->nullable()->after('brand_sections');
            $table->string('person_role', 255)->nullable()->after('person_phone');
            $table->text('person_bio')->nullable()->after('person_role');
            $table->string('person_email', 255)->nullable()->after('person_bio');
            $table->json('person_social')->nullable()->after('person_email');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['person_phone', 'person_role', 'person_bio', 'person_email', 'person_social']);
        });
    }
};
