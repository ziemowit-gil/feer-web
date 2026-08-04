<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wyniki automatycznych skanów WCAG poszczególnych podstron serwisu. */
    public function up(): void
    {
        Schema::create('wcag_scans', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('page_title')->nullable();
            $table->json('issues');
            $table->unsignedSmallInteger('issue_count')->default(0);
            $table->timestamp('scanned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wcag_scans');
    }
};
