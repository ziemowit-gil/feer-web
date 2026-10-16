<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uprawnienia newslettera (App\Support\NewsletterPermissions): gotowe grupy użytkowników
 * „Redakcja newslettera” i „Wydawcy newslettera” z zestawami jak role o tych nazwach —
 * do łączenia newslettera z innymi modułami (rola „Edytor (grupy)”). Role nie wymagają
 * zmian w bazie (users.role to zwykły tekst). Istniejących grup nie nadpisuje.
 */
return new class extends Migration
{
    private const GROUPS = [
        'Redakcja newslettera' => ['newsletter.view', 'newsletter.campaigns', 'newsletter.templates', 'newsletter.reports'],
        'Wydawcy newslettera' => ['newsletter.view', 'newsletter.campaigns', 'newsletter.send', 'newsletter.templates', 'newsletter.subscribers', 'newsletter.reports'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('user_groups')) {
            return;
        }

        foreach (self::GROUPS as $name => $permissions) {
            if (DB::table('user_groups')->where('name', $name)->exists()) {
                continue;
            }
            DB::table('user_groups')->insert([
                'name' => $name,
                'modules' => json_encode($permissions),
                'can_approve' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_groups')) {
            return;
        }

        // Usuwa tylko grupy bez przypisanych osób.
        foreach (array_keys(self::GROUPS) as $name) {
            $id = DB::table('user_groups')->where('name', $name)->value('id');
            if ($id && ! DB::table('users')->where('user_group_id', $id)->exists()) {
                DB::table('user_groups')->where('id', $id)->delete();
            }
        }
    }
};
