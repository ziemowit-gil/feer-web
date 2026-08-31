<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('spheres')->nullable()->after('type');
        });

        foreach (DB::table('organizations')->whereNotNull('sphere')->get(['id', 'sphere']) as $row) {
            DB::table('organizations')->where('id', $row->id)->update(['spheres' => json_encode([$row->sphere])]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('sphere');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('sphere')->nullable()->after('type');
        });

        foreach (DB::table('organizations')->whereNotNull('spheres')->get(['id', 'spheres']) as $row) {
            $spheres = json_decode($row->spheres, true) ?: [];
            DB::table('organizations')->where('id', $row->id)->update(['sphere' => $spheres[0] ?? null]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('spheres');
        });
    }
};
