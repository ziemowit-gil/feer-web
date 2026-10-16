<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavItem;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

/**
 * Eksport i import struktury menu w XML (Menu nawigacji → „Eksport XML” / „Dodaj → Z pliku XML”).
 *
 * Format: <menu location="main"> z zagnieżdżonymi <item> — pola skalarne jako atrybuty,
 * linki karty bocznej mega menu jako <side-link>. Import zawsze DOPISUJE pozycje na końcu
 * wybranego menu (nie kasuje istniejących), więc plik z eksportu można bezpiecznie
 * wczytać do innej witryny albo innej lokalizacji.
 */
class NavItemXmlController extends Controller
{
    /** Pola tekstowe przenoszone jako atrybuty <item>. */
    private const TEXT = ['label', 'url', 'type', 'icon', 'description', 'module', 'mega_image', 'mega_image_alt',
        'mega_size', 'mega_extra_title', 'mega_side_title', 'button_color', 'accent_color'];

    /** Pola logiczne (atrybut "1"/"0"). */
    private const BOOL = ['is_active', 'is_button', 'is_column_heading', 'is_transparent_dropdown', 'is_mega',
        'hide_all_projects_btn', 'hide_archive_btn', 'highlight'];

    private const MAX_ITEMS = 500;

    public function export(Request $request)
    {
        $location = $request->query('location', 'main');
        abort_unless(array_key_exists($location, NavItem::LOCATIONS), 404);

        $items = NavItem::forCurrentSite()->where('location', $location)->orderBy('order')->orderBy('id')->get();
        $byParent = $items->groupBy(fn ($i) => (int) $i->parent_id);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><menu/>');
        $xml->addAttribute('location', $location);
        $xml->addAttribute('site', (string) SiteSetting::current()->site_name);
        $xml->addAttribute('exported', now()->toIso8601String());
        $xml->addAttribute('version', '1');
        $this->appendItems($xml, $byParent, 0);

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        $file = 'menu-'.$location.'-'.now()->format('Y-m-d').'.xml';

        return response($dom->saveXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$file.'"',
        ]);
    }

    private function appendItems(SimpleXMLElement $node, Collection $byParent, int $parentId): void
    {
        foreach ($byParent->get($parentId, collect()) as $item) {
            $el = $node->addChild('item');
            foreach (self::TEXT as $field) {
                if (filled($item->{$field})) {
                    $el->addAttribute($field, (string) $item->{$field});
                }
            }
            foreach (self::BOOL as $field) {
                if ($item->{$field} !== null) {
                    $el->addAttribute($field, $item->{$field} ? '1' : '0');
                }
            }
            foreach ((array) $item->mega_side_links as $link) {
                $sl = $el->addChild('side-link');
                foreach (['label', 'url', 'style'] as $f) {
                    if (filled($link[$f] ?? null)) {
                        $sl->addAttribute($f, (string) $link[$f]);
                    }
                }
                $sl->addAttribute('new_tab', ! empty($link['new_tab']) ? '1' : '0');
            }
            $this->appendItems($el, $byParent, $item->id);
        }
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'location' => ['required', Rule::in(array_keys(NavItem::LOCATIONS))],
            'parent_id' => ['nullable', 'integer', Rule::exists('nav_items', 'id')->whereNull('parent_id')],
            'xml' => ['required', 'file', 'max:1024'],
            // Podgląd w oknie importu: ścieżki odznaczonych pozycji („0”, „0.2” — indeksy <item> od góry); pomijane razem z podpozycjami.
            'skip' => ['nullable', 'array', 'max:'.self::MAX_ITEMS],
            'skip.*' => ['string', 'max:40', 'regex:/^\d+(\.\d+)*$/'],
        ], ['xml.required' => 'Wybierz plik XML ze strukturą menu.']);

        $raw = (string) file_get_contents($request->file('xml')->getRealPath());
        // Bez DTD: blokuje encje zewnętrzne i „billion laughs”.
        if (stripos($raw, '<!DOCTYPE') !== false || stripos($raw, '<!ENTITY') !== false) {
            throw ValidationException::withMessages(['xml' => 'Plik XML nie może zawierać deklaracji DOCTYPE ani ENTITY.']);
        }
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (! $xml || $xml->getName() !== 'menu') {
            throw ValidationException::withMessages(['xml' => 'To nie jest plik struktury menu (oczekiwano elementu <menu> z pozycjami <item>).']);
        }
        if (count($xml->xpath('//item')) > self::MAX_ITEMS) {
            throw ValidationException::withMessages(['xml' => 'Plik ma za dużo pozycji (maks. '.self::MAX_ITEMS.').']);
        }

        $location = $data['location'];
        // Podpozycje istnieją tylko w menu głównym — w stopce i menu BIP struktura jest spłaszczana.
        $nested = $location === 'main';
        $parentId = $nested ? ($data['parent_id'] ?? null) : null;
        if ($parentId && ! NavItem::forCurrentSite()->where('location', $location)->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Wybrana pozycja nadrzędna nie należy do tego menu.']);
        }

        $hasHighlight = Schema::hasColumn('nav_items', 'highlight');
        $skip = array_flip($data['skip'] ?? []);
        $added = 0;
        $first = null;

        DB::transaction(function () use ($xml, $location, $nested, $parentId, $hasHighlight, $skip, &$added, &$first) {
            $create = function (SimpleXMLElement $node, ?int $parent, string $path) use (&$create, $location, $nested, $hasHighlight, $skip, &$added, &$first) {
                $n = 0;
                foreach ($node->item as $el) {
                    // Numeracja jak w podglądzie okna importu: indeks <item> wśród rodzeństwa, od zera.
                    $elPath = ($path === '' ? '' : $path.'.').$n++;
                    if (isset($skip[$elPath])) {
                        continue;
                    }
                    $attrs = $this->sanitize($el, $hasHighlight);
                    if ($attrs === null) {
                        continue;
                    }
                    $order = (int) NavItem::forCurrentSite()->where('location', $location)->where('parent_id', $parent)->max('order');
                    $item = NavItem::create($attrs + ['location' => $location, 'parent_id' => $parent, 'order' => $order + 1]);
                    $first ??= $item;
                    $added++;
                    // Menu główne: dwa poziomy (pozycja + podpozycje); głębsze gałęzie i inne menu — spłaszczone na ten sam poziom.
                    $create($el, $nested && $parent === null ? $item->id : $parent, $elPath);
                }
            };
            $create($xml, $parentId, '');
        });

        return redirect()->route('admin.pozycje-menu.index', array_filter(['location' => $location, 'pozycja' => $first?->id]))
            ->with('status', $added ? "Zaimportowano pozycji menu z pliku XML: {$added}." : 'Plik XML nie zawierał żadnych pozycji do dodania.');
    }

    /** Atrybuty <item> zamienione na bezpieczne pola NavItem (null = pominąć pozycję bez etykiety). */
    private function sanitize(SimpleXMLElement $el, bool $hasHighlight): ?array
    {
        $a = fn (string $k) => isset($el[$k]) ? trim((string) $el[$k]) : null;
        $label = mb_substr((string) $a('label'), 0, 255);
        if ($label === '') {
            return null;
        }

        $safeUrl = fn (?string $u, int $max) => ($u === null || $u === '' || preg_match('/^\s*(javascript|data|vbscript):/i', $u)) ? null : mb_substr($u, 0, $max);
        $color = fn (?string $c) => $c !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : null;
        $text = fn (?string $v, int $max) => $v === null || $v === '' ? null : mb_substr($v, 0, $max);

        $type = $a('type');
        $type = array_key_exists((string) $type, NavItem::TYPES) && ! SiteSetting::current()->isOptionBlocked('nav_item_types', $type) ? $type : 'link';
        $module = $a('module');

        $attrs = [
            'label' => $label,
            'url' => $safeUrl($a('url'), 255) ?? '#',
            'type' => $type,
            'icon' => $text($a('icon'), 100),
            'description' => $text($a('description'), 255),
            'module' => array_key_exists((string) $module, SiteSetting::MODULES) ? $module : null,
            'mega_image' => $safeUrl($a('mega_image'), 500),
            'mega_image_alt' => $text($a('mega_image_alt'), 255),
            'mega_size' => array_key_exists((string) $a('mega_size'), NavItem::MEGA_SIZES) ? $a('mega_size') : null,
            'mega_extra_title' => $text($a('mega_extra_title'), 80),
            'mega_side_title' => $text($a('mega_side_title'), 80),
            'button_color' => $color($a('button_color')),
            'accent_color' => $color($a('accent_color')),
        ];
        foreach (self::BOOL as $field) {
            if ($field === 'highlight' && ! $hasHighlight) {
                continue;
            }
            $attrs[$field] = $field === 'is_active' ? $a($field) !== '0' : $a($field) === '1';
        }

        $links = [];
        foreach ($el->{'side-link'} as $sl) {
            $url = $safeUrl(isset($sl['url']) ? trim((string) $sl['url']) : null, 500);
            $style = (string) ($sl['style'] ?? 'tile');
            if ($url && count($links) < 8) {
                $links[] = [
                    'label' => mb_substr(trim((string) ($sl['label'] ?? '')), 0, 80),
                    'url' => $url,
                    'style' => in_array($style, NavItem::SIDE_LINK_STYLES, true) ? $style : 'tile',
                    'new_tab' => (string) ($sl['new_tab'] ?? '0') === '1',
                ];
            }
        }
        $attrs['mega_side_links'] = $links ?: null;
        // Kolumny z wartością domyślną w bazie (NOT NULL) — bez wpisu zostaje domyślna.
        if ($attrs['mega_size'] === null) {
            unset($attrs['mega_size']);
        }

        return $attrs;
    }
}
