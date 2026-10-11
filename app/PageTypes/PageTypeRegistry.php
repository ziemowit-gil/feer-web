<?php

namespace App\PageTypes;

/**
 * Rejestr typów stron: wykrywa klasy z app/PageTypes/Types/*.php (każda to
 * jeden typ, patrz PageType) i udostępnia zestawienia używane w modelu Page,
 * kontrolerze panelu i widokach. Wynik trzymany w pamięci na czas żądania.
 *
 * register() pozwala dodać typ spoza katalogu (testy, moduły); reset()
 * przywraca stan z samego katalogu.
 */
class PageTypeRegistry
{
    /** Grupy typów w kolejności wyświetlania (wybór typu w edycji, filtr listy stron). */
    public const GROUPS = [
        'Treść i informacja',
        'Oferta i edukacja',
        'Organizacja',
        'Kafelki i nawigacja',
        'Kontakt i wydarzenia',
        'Wewnętrzne i przekierowania',
    ];

    /** @var array<string, PageType>|null Typy wg klucza, posortowane wg order(). */
    private static ?array $types = null;

    /** @var array<string, PageType> Typy dodane przez register(). */
    private static array $registered = [];

    /** @var array<string, mixed> Pamięć zestawień pochodnych (rules, listKeys…). */
    private static array $memo = [];

    /** @return array<string, PageType> */
    public static function all(): array
    {
        if (self::$types === null) {
            $types = [];
            foreach (glob(__DIR__.'/Types/*.php') ?: [] as $file) {
                $class = __NAMESPACE__.'\\Types\\'.basename($file, '.php');
                if (class_exists($class) && is_subclass_of($class, PageType::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                    $type = new $class;
                    $types[$type->key()] = $type;
                }
            }
            $types = array_merge($types, self::$registered);
            uasort($types, fn (PageType $a, PageType $b) => $a->order() <=> $b->order());
            self::$types = $types;
        }

        return self::$types;
    }

    public static function get(?string $key): ?PageType
    {
        return $key === null ? null : (self::all()[$key] ?? null);
    }

    public static function has(?string $key): bool
    {
        return self::get($key) !== null;
    }

    /** Dodaje (lub podmienia) typ spoza app/PageTypes/Types — dla testów i modułów. */
    public static function register(PageType $type): void
    {
        self::$registered[$type->key()] = $type;
        self::flush();
    }

    /** Przywraca rejestr do typów z katalogu (usuwa dodane przez register()). */
    public static function reset(): void
    {
        self::$registered = [];
        self::flush();
    }

    private static function flush(): void
    {
        self::$types = null;
        self::$memo = [];
    }

    /** @return array<string, string> klucz => etykieta (dawne Page::TYPES). */
    public static function labels(): array
    {
        return self::$memo['labels'] ??= array_map(fn (PageType $t) => $t->label(), self::all());
    }

    /** @return array<string, array<int, string>> grupa => klucze typów (dawne Page::TYPE_GROUPS). */
    public static function groups(): array
    {
        if (isset(self::$memo['groups'])) {
            return self::$memo['groups'];
        }

        $types = self::all();
        uasort($types, fn (PageType $a, PageType $b) => [$a->groupOrder(), $a->order()] <=> [$b->groupOrder(), $b->order()]);

        $groups = array_fill_keys(self::GROUPS, []);
        foreach ($types as $key => $type) {
            $groups[$type->group()][] = $key;
        }

        return self::$memo['groups'] = array_filter($groups);
    }

    /** @return array<string, string> klucz => ikona Font Awesome (dawne Page::TYPE_ICONS; typy bez ikony pominięte). */
    public static function icons(): array
    {
        return self::$memo['icons'] ??= array_filter(array_map(fn (PageType $t) => $t->icon(), self::all()));
    }

    /** @return array<int, string> typy z danymi w kolumnie `type_data` (dawne Page::TYPE_DATA_TYPES). */
    public static function typeDataTypes(): array
    {
        return self::$memo['typeData'] ??= array_keys(array_filter(self::all(), fn (PageType $t) => $t->usesTypeData()));
    }

    /**
     * Scalone reguły `type_data.*` wszystkich typów. Ten sam klucz może deklarować
     * kilka typów (lead, cta_*, docs…) — obowiązuje pierwsza deklaracja w kolejności typów.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        if (! isset(self::$memo['rules'])) {
            $rules = [];
            foreach (self::all() as $type) {
                $rules += $type->rules();
            }
            self::$memo['rules'] = $rules;
        }

        return self::$memo['rules'];
    }

    /** @return array<int, string> scalone klucze list `type_data` (normalizowane w Page::typeData()). */
    public static function listKeys(): array
    {
        return self::$memo['listKeys'] ??= array_values(array_unique(array_merge(
            ...array_values(array_map(fn (PageType $t) => $t->listKeys(), self::all()))
        )));
    }
}
