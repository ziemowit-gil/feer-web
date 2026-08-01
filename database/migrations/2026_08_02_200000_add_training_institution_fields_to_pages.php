<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('training_manager_name', 255)->nullable()->after('legacy_intro');
            $table->string('training_manager_title', 255)->nullable()->after('training_manager_name');
            $table->string('training_ris_number', 100)->nullable()->after('training_manager_title');
            $table->string('training_bur_number', 100)->nullable()->after('training_ris_number');
            $table->text('training_extra_info')->nullable()->after('training_bur_number');
            $table->text('training_bur_note')->nullable()->after('training_extra_info');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'training_manager_name',
                'training_manager_title',
                'training_ris_number',
                'training_bur_number',
                'training_extra_info',
                'training_bur_note',
            ]);
        });
    }
};
