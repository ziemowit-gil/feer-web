<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sklep_orders', function (Blueprint $table) {
            $table->dropColumn('educational_material_id');
            $table->unsignedInteger('subtotal_grosze')->default(0)->after('buyer_email');
            $table->unsignedBigInteger('discount_code_id')->nullable()->after('subtotal_grosze');
            $table->unsignedInteger('discount_amount_grosze')->default(0)->after('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('sklep_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_grosze', 'discount_code_id', 'discount_amount_grosze']);
            $table->unsignedBigInteger('educational_material_id')->nullable();
        });
    }
};
