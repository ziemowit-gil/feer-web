<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('cloned_from_id')->nullable()->after('is_clone')
                ->constrained('news')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\News::class, 'cloned_from_id');
            $table->dropColumn('cloned_from_id');
        });
    }
};
