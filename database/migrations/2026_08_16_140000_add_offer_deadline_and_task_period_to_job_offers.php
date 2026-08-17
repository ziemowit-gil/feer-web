<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->date('offer_deadline')->nullable()->after('grant_condition');
            $table->string('task_period', 255)->nullable()->after('offer_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn(['offer_deadline', 'task_period']);
        });
    }
};
