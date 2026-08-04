<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('news', function (Blueprint $table) {
            $table->unsignedTinyInteger('image_focal_x')->default(50)->after('image_alt');
            $table->unsignedTinyInteger('image_focal_y')->default(50)->after('image_focal_x');
        });
    }
    public function down(): void {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['image_focal_x', 'image_focal_y']);
        });
    }
};
