<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uprawnienia jak w TYPO3 (lekko):
 * - user_group_user: dodatkowe grupy osoby (uprawnienia z grup się sumują, najszersza reguła wygrywa),
 * - user_groups.block_types / locked_fields: dozwolone typy bloków edytora i pola, których grupa nie zmienia,
 * - users.active_from / active_until: konto ważne od–do (praktykant, audytor), expiry_handled_at: wygaszenie przez cron.
 *
 * Idempotentna — kod działa też przed migracją (push wdraża bez `migrate`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_group_user')) {
            Schema::create('user_group_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_group_id')->constrained('user_groups')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'user_group_id']);
            });
        }

        Schema::table('user_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('user_groups', 'block_types')) {
                $table->json('block_types')->nullable();
            }
            if (! Schema::hasColumn('user_groups', 'locked_fields')) {
                $table->json('locked_fields')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active_from')) {
                $table->timestamp('active_from')->nullable();
            }
            if (! Schema::hasColumn('users', 'active_until')) {
                $table->timestamp('active_until')->nullable();
            }
            if (! Schema::hasColumn('users', 'expiry_handled_at')) {
                $table->timestamp('expiry_handled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_group_user');
        Schema::table('user_groups', function (Blueprint $table) {
            foreach (['block_types', 'locked_fields'] as $column) {
                if (Schema::hasColumn('user_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('users', function (Blueprint $table) {
            foreach (['active_from', 'active_until', 'expiry_handled_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
