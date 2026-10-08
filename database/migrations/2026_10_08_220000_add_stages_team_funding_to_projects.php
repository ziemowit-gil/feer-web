<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status', 10)->nullable();   // planned | active | completed
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('stages')->nullable();         // [{title, from, to, state: done|current|upcoming, text}]
            $table->json('team')->nullable();           // [{name, role, text}]
            $table->json('funding')->nullable();        // {sources: [{name, text, url}], budget, budget_public}
            $table->text('funding_notice')->nullable(); // obowiązkowe oznaczenie dofinansowania
        });

        Schema::create('project_partner', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('partner_id');
            $table->primary(['project_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_partner');
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['status', 'starts_on', 'ends_on', 'stages', 'team', 'funding', 'funding_notice']));
    }
};
