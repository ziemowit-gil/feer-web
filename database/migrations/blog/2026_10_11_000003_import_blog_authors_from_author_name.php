<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The "Wiem FEER" blog lives in its own SQLite database.
     *
     * Migracja danych: każdy niepusty, unikalny `author_name` ze starych wpisów
     * staje się profilem autora, a wpisy bez `author_id` dostają go jako autora
     * głównego. Idempotentna — autorów szuka po slugu, wpisów z ustawionym
     * `author_id` nie rusza. `author_name` zostaje bez zmian (zapas).
     */
    protected $connection = 'blog';

    public function up(): void
    {
        $db = DB::connection('blog');

        $names = $db->table('blog_articles')
            ->whereNull('author_id')
            ->whereNotNull('author_name')
            ->where('author_name', '!=', '')
            ->distinct()
            ->pluck('author_name');

        foreach ($names as $rawName) {
            $name = trim(preg_replace('/\s+/u', ' ', (string) $rawName));
            $slug = Str::slug($name);
            if ($name === '' || $slug === '') {
                continue;
            }

            $authorId = $db->table('blog_authors')->where('slug', $slug)->value('id');

            if (! $authorId) {
                $authorId = $db->table('blog_authors')->insertGetId([
                    'name'       => $name,
                    'slug'       => $slug,
                    'is_active'  => true,
                    'show_email' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $db->table('blog_articles')
                ->whereNull('author_id')
                ->where('author_name', $rawName)
                ->update(['author_id' => $authorId]);
        }
    }

    public function down(): void
    {
        // Migracja danych — bez cofania (author_name pozostał nietknięty).
    }
};
