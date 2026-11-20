<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Uprawnienia grup do edytora treści (jak „Allowed content types” i „Exclude fields” w TYPO3):
 *
 * - dozwolone typy bloków edytora blokowego (user_groups.block_types; puste = wszystkie). Blok spoza listy
 *   nie pojawia się we wstawianiu, a serwer nie przyjmie nowego — istniejące bloki tego typu zostają nietknięte
 *   (redaktor może je przesunąć albo usunąć, ale nie zmienić),
 * - pola, których grupa nie zmienia (user_groups.locked_fields), np. adres strony albo terminy publikacji:
 *   edytor ich nie pokazuje, a przy zapisie rekordu zostają stare wartości (guardLocked()).
 *
 * Przy kilku grupach wygrywa najszersza reguła: typ bloku jest dozwolony, gdy pozwala na niego któraś grupa,
 * a pole jest zablokowane tylko wtedy, gdy blokują je wszystkie grupy osoby.
 */
class EditorPermissions
{
    /** Pola do zablokowania: klucz => [etykieta, kolumny stron / aktualności / działań]. */
    public const LOCKABLE_FIELDS = [
        'slug' => ['Adres (slug)', ['slug']],
        'schedule' => ['Terminy: publikacja od, zdjęcie z publikacji, wygaśnięcie', ['publish_at', 'unpublish_at', 'expires_at']],
        'seo' => ['SEO: meta tytuł i meta opis', ['meta_title', 'meta_description']],
        'access' => ['Dostęp do strony: hasło, grupy, logowanie', ['access_mode', 'access_password', 'access_level', 'access_group_ids']],
    ];

    /** Typy bloków, które grupa może dostać (części kontenerów — kolumna, zakładka — idą razem z kontenerem). */
    public static function blockTypeOptions(): array
    {
        return collect(BlockSummary::TYPES)
            ->reject(fn ($v, $type) => in_array($type, ['column', 'tab'], true))
            ->map(fn ($v) => $v[0])
            ->all();
    }

    /** Kolumny zablokowane dla osoby (puste dla administratora i ról bez grup). @return array<int, string> */
    public static function lockedColumns(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect($user->lockedFields())
            ->flatMap(fn ($key) => self::LOCKABLE_FIELDS[$key][1] ?? [])
            ->values()->all();
    }

    /**
     * Zapis rekordu przez osobę z zablokowanymi polami: zablokowane kolumny wracają do starych wartości
     * (każda droga zapisu — formularz, edytor blokowy, edycja na żywo — bez zmian w kontrolerach).
     */
    public static function guardLocked(Model $model): void
    {
        if (! $model->exists) {
            return;
        }
        foreach (self::lockedColumns(auth()->user()) as $column) {
            if ($model->isDirty($column)) {
                $model->setAttribute($column, $model->getOriginal($column));
            }
        }
    }

    /**
     * Drzewo bloków po zapisie osoby z ograniczonymi typami: nowy blok niedozwolonego typu nie przechodzi
     * (zwykły blok znika, kontener zostawia swoją treść), a taki sam jak w zapisanej treści zostaje —
     * redaktor może go przesunąć albo usunąć. Porównanie po treści bloku (bez ukrycia i harmonogramu).
     *
     * @param  array<int, array<string, mixed>>  $blocks  bloki po normalizacji
     * @param  array<int, array<string, mixed>>|null  $previous  zapisane bloki rekordu
     * @param  array<int, string>|null  $allowed  null = wszystkie typy
     * @return array<int, array<string, mixed>>
     */
    public static function enforceBlockTypes(array $blocks, ?array $previous, ?array $allowed): array
    {
        if ($allowed === null) {
            return $blocks;
        }
        $pool = [];
        self::collectSignatures($previous ?? [], $pool);

        return self::filterBlocks($blocks, $allowed, $pool);
    }

    /** Czy osoba może dodać blok tego typu (części kontenerów idą za kontenerem). */
    public static function allowsType(?array $allowed, string $type): bool
    {
        $parent = ['column' => 'columns', 'tab' => 'tabs'][$type] ?? $type;

        return $allowed === null || in_array($parent, $allowed, true);
    }

    private static function filterBlocks(array $blocks, array $allowed, array &$pool): array
    {
        $out = [];
        foreach ($blocks as $b) {
            $type = (string) ($b['type'] ?? '');
            $isContainer = isset($b['inner']);
            if (! self::allowsType($allowed, $type)) {
                $sig = self::signature($b);
                if (($pool[$sig] ?? 0) > 0) {
                    $pool[$sig]--;
                    // Istniejący kontener: jego treść też podlega sprawdzeniu.
                    if ($isContainer) {
                        $b['inner'] = self::filterBlocks($b['inner'], $allowed, $pool);
                    }
                    $out[] = $b;
                } elseif ($isContainer) {
                    array_push($out, ...self::filterBlocks(self::partsContent($b), $allowed, $pool));
                }

                continue;
            }
            if ($isContainer) {
                $b['inner'] = self::filterBlocks($b['inner'], $allowed, $pool);
            }
            $out[] = $b;
        }

        return $out;
    }

    /** Treść kontenera bez opakowania (kolumny i zakładki oddają bloki swoich części). */
    private static function partsContent(array $b): array
    {
        $inner = (array) ($b['inner'] ?? []);
        if (in_array($b['type'] ?? '', ['columns', 'tabs'], true)) {
            return array_merge(...array_map(fn ($p) => (array) ($p['inner'] ?? []), $inner ?: [[]]));
        }

        return $inner;
    }

    private static function collectSignatures(array $blocks, array &$pool): void
    {
        foreach ($blocks as $b) {
            if (! is_array($b)) {
                continue;
            }
            $sig = self::signature($b);
            $pool[$sig] = ($pool[$sig] ?? 0) + 1;
            if (is_array($b['inner'] ?? null)) {
                self::collectSignatures($b['inner'], $pool);
            }
        }
    }

    /** Odcisk bloku: typ i atrybuty (kontener bez treści w środku — tę sprawdzamy osobno). */
    private static function signature(array $b): string
    {
        return md5(json_encode([(string) ($b['type'] ?? ''), $b['attrs'] ?? []]));
    }
}
