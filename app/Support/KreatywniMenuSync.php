<?php

namespace App\Support;

use App\Models\NavItem;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * „Odwzoruj menu w panelu 1:1” dla szablonu „kreatywni”: mega menu dobudowuje pozycjom bez własnych podpozycji podmenu
 * (podstrony powiązanej strony i sekcje H2/H3 jej treści — KreatywniMegaMenu), którego nie widać w Menu nawigacji.
 * Ta klasa zapisuje je jako zwykłe podpozycje: nagłówek sekcji (H2) jako „nagłówek kolumny”, linki pod nim jako
 * podpozycje, w tej samej kolejności. Pozycja z własnymi podpozycjami pokazuje na stronie dokładnie je (bez
 * dobudowywania), więc po odwzorowaniu panel i strona są zgodne 1:1, a podmenu da się edytować, ukrywać i przestawiać.
 *
 * Wymaga, żeby $site była bieżącą witryną (SiteSetting::current()) — adresy liczy KreatywniMegaMenu dla bieżącej witryny.
 */
final class KreatywniMenuSync
{
    /**
     * Pozycje menu głównego bez podpozycji, którym strona dobudowuje podmenu.
     *
     * @return Collection<int, array{nav: NavItem, groups: array<int, array{heading: ?array, links: array}>}>
     */
    public static function plan(SiteSetting $site): Collection
    {
        $top = NavItem::withoutGlobalScopes()->where('site_id', $site->getKey())->where('location', 'main')
            ->whereNull('parent_id')->where('is_active', true)->orderBy('order')->with('children')->get();
        if ($top->isEmpty()) {
            return collect();
        }

        $withChildren = NavItem::withoutGlobalScopes()->whereIn('parent_id', $top->modelKeys())->distinct()->pluck('parent_id')
            ->map(fn ($id) => (int) $id)->all();
        $menu = KreatywniMegaMenu::items($top)->values();

        return $top->values()
            ->map(fn (NavItem $nav, int $i) => ['nav' => $nav, 'groups' => $menu[$i]->groups ?? []])
            ->filter(fn (array $row) => $row['groups'] !== [] && ! in_array((int) $row['nav']->getKey(), $withChildren, true))
            ->values();
    }

    /** Zapisuje dobudowane podmenu jako podpozycje. @return int liczba utworzonych podpozycji */
    public static function materialize(SiteSetting $site): int
    {
        $plan = self::plan($site);
        $created = 0;

        DB::transaction(function () use ($plan, $site, &$created) {
            foreach ($plan as ['nav' => $nav, 'groups' => $groups]) {
                $order = 1;
                foreach ($groups as $group) {
                    if ($group['heading']) {
                        self::child($nav, $site, $group['heading']['label'], $group['heading']['url'] ?? null, $order++, true);
                        $created++;
                    }
                    foreach ($group['links'] as $link) {
                        self::child($nav, $site, $link['label'], $link['url'], $order++, false);
                        $created++;
                    }
                }
            }
        });

        return $created;
    }

    private static function child(NavItem $parent, SiteSetting $site, string $label, ?string $url, int $order, bool $heading): void
    {
        NavItem::withoutGlobalScopes()->create([
            'site_id' => $site->getKey(),
            'parent_id' => $parent->getKey(),
            'location' => 'main',
            'type' => 'link',
            'label' => mb_substr(trim($label), 0, 255),
            'url' => self::relative($url, $site),
            'is_column_heading' => $heading,
            'is_active' => true,
            'order' => $order,
        ]);
    }

    /** Adres w witrynie bez domeny i prefiksu witryny (/oferta#laleczki) — taki, jaki zapisuje się w Menu nawigacji. */
    public static function relative(?string $url, SiteSetting $site): ?string
    {
        if (blank($url)) {
            return null;
        }
        $parts = parse_url((string) $url);
        $path = $parts['path'] ?? '/';
        $prefix = rtrim((string) parse_url(current_site_url($site), PHP_URL_PATH), '/');
        if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix.'/'))) {
            $path = substr($path, strlen($prefix)) ?: '/';
        }

        return $path.(isset($parts['fragment']) && $parts['fragment'] !== '' ? '#'.$parts['fragment'] : '');
    }
}
