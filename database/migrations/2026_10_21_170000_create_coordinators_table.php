<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł „Koordynatorzy”: osoby kontaktowe wybierane w działaniach (zamiast wpisywania danych w każdym działaniu).
 * Z danych już wpisanych w działaniach zakładamy koordynatorów i od razu ich przypisujemy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('name', 160);
            $table->string('email', 255)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('phone_hours', 160)->nullable();
            $table->foreignId('person_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::table('projects', fn (Blueprint $table) => $table->foreignId('coordinator_id')->nullable()->constrained('coordinators')->nullOnDelete());

        $made = [];
        foreach (DB::table('projects')->whereNotNull('coordinator_name')->where('coordinator_name', '!=', '')->orderBy('id')->get() as $p) {
            $key = ($p->site_id ?? 0).'|'.mb_strtolower(trim((string) ($p->coordinator_email ?: $p->coordinator_name)));
            if (! isset($made[$key])) {
                $made[$key] = DB::table('coordinators')->insertGetId([
                    'site_id' => $p->site_id, 'name' => trim($p->coordinator_name), 'email' => $p->coordinator_email ?: null,
                    'phone' => $p->coordinator_phone ?: null, 'phone_hours' => $p->coordinator_phone_hours ?: null,
                    'order' => count($made), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('projects')->where('id', $p->id)->update(['coordinator_id' => $made[$key]]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropConstrainedForeignId('coordinator_id'));
        Schema::dropIfExists('coordinators');
    }
};
