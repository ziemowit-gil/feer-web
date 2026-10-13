<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Project;
use App\Models\BipDocument;
use App\Models\MediaLibrary;
use App\Models\Slider;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Panel admin: wyszukiwarka poleceń (Ctrl+K) — treści i sekcje panelu,
 * filtrowana według uprawnień zalogowanego użytkownika.
 *
 * Metody: __invoke().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SearchController extends Controller
{
    /**
     * Wyszukiwarka panelu (paleta poleceń Ctrl+K): treści oraz sekcje panelu,
     * filtrowane po uprawnieniach do modułów.
     *
     * @var array<int, array{0:string,1:class-string,2:string,3:string,4:string,5:string}>
     */
    private const CONTENT = [
        // [moduł, model, pole, trasa edycji, etykieta typu, ikona, where-closure|null]
        ['pages', Page::class, 'title', 'admin.podstrony.edit', 'Strona', 'fa-file-lines', ['type', '!=', 'about_person']],
        ['pages', Page::class, 'title', 'admin.podstrony.edit', 'Osoba', 'fa-user', ['type', '=', 'about_person']],
        ['news', News::class, 'title', 'admin.newsy.edit', 'Aktualność', 'fa-newspaper', null],
        ['projects', Project::class, 'title', 'admin.projekty.edit', 'Działanie', 'fa-diagram-project', null],
        ['events', Event::class, 'title', 'admin.wydarzenia.edit', 'Wydarzenie', 'fa-calendar-days', null],
        ['partners', Partner::class, 'name', 'admin.partnerzy.edit', 'Partner', 'fa-handshake', null],
        ['faq', Faq::class, 'question', 'admin.faq.edit', 'FAQ', 'fa-circle-question', null],
        ['bip', BipDocument::class, 'title', 'admin.bip-dokumenty.edit', 'Dokument BIP', 'fa-landmark', null],
        ['hero', Slider::class, 'name', 'admin.slidery.show', 'Slider', 'fa-images', null],
    ];

    /** Typy, w których szukamy też w treści (z fragmentem wokół trafienia). */
    private const BODY_FIELDS = [
        Page::class => 'content',
        News::class => 'content',
        Project::class => 'content',
        BipDocument::class => 'content',
    ];

    /** Obsługuje wyszukiwarkę poleceń Ctrl+K — treści i sekcje panelu. */
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $results = [];

        $like = '%'.addcslashes($q, '%_\\').'%';

        foreach (self::CONTENT as [$module, $class, $field, $route, $label, $icon, $where]) {
            if (! $user->canAccessModule($module) || ! Route::has($route) || ! class_exists($class)) {
                continue;
            }

            $body = self::BODY_FIELDS[$class] ?? null;
            $query = $class::query()->where(fn ($w) => $w->where($field, 'like', $like)
                ->when($body, fn ($w) => $w->orWhere($body, 'like', $like)));
            if ($where !== null) {
                $query->where($where[0], $where[1], $where[2]);
            }

            // Najpierw trafienia w tytule, potem w treści.
            $models = $query->limit(20)->get()
                ->sortBy(fn ($m) => mb_stripos((string) $m->{$field}, $q) !== false ? 0 : 1)
                ->take(5);

            foreach ($models as $model) {
                $secondary = null;
                if ($label === 'Osoba') {
                    $secondary = $model->person_role;
                } elseif ($body && mb_stripos((string) $model->{$field}, $q) === false) {
                    $secondary = $this->snippet((string) $model->{$body}, $q);
                }
                $results[] = [
                    'label'     => $label,
                    'title'     => (string) $model->{$field},
                    'secondary' => $secondary,
                    'url'       => route($route, $model),
                    'icon'      => $icon,
                ];
            }
        }

        // Użytkownicy — tylko dla adminów.
        if ($user->isAdmin()) {
            foreach (User::where('name', 'like', '%' . $q . '%')->orWhere('email', 'like', '%' . $q . '%')->limit(5)->get() as $model) {
                $results[] = [
                    'label' => 'Użytkownik',
                    'title' => $model->name . ' (' . $model->email . ')',
                    'url' => route('admin.uzytkownicy.edit', $model),
                    'icon' => 'fa-user',
                ];
            }
        }

        // Pliki z biblioteki multimediów (nazwa pliku albo nazwa wyświetlana).
        // Multimedia są dostępne dla każdego użytkownika panelu.
        if (Route::has('admin.multimedia.index')) {
            $files = config('media-library.media_model')::query()
                ->where('model_type', MediaLibrary::class)
                ->where(fn ($w) => $w->where('file_name', 'like', $like)->orWhere('name', 'like', $like)
                    ->orWhere('custom_properties', 'like', '%"display_name":"%'.addcslashes($q, '%_\\').'%'))
                ->latest()->limit(5)->get();
            foreach ($files as $file) {
                $results[] = [
                    'label' => 'Plik',
                    'title' => (string) ($file->getCustomProperty('display_name') ?: $file->file_name),
                    'secondary' => strtoupper(pathinfo($file->file_name, PATHINFO_EXTENSION)).' · '.$file->human_readable_size,
                    'url' => route('admin.multimedia.index', ['q' => $file->file_name]),
                    'icon' => str_starts_with((string) $file->mime_type, 'image/') ? 'fa-image' : 'fa-file',
                ];
            }
        }

        // Sekcje panelu pasujące do frazy (całe menu, także zakładki ustawień).
        foreach ($this->sections($user) as $section) {
            if (mb_stripos($section['title'], $q) !== false || mb_stripos((string) ($section['secondary'] ?? ''), $q) !== false) {
                $results[] = $section + ['label' => 'Sekcja', 'icon' => 'fa-compass'];
            }
        }

        return response()->json(['results' => array_slice($results, 0, 30)]);
    }

    /** Cele nawigacji: wszystkie pozycje menu panelu (z poszanowaniem uprawnień), z podpozycjami. */
    private function sections(User $user): array
    {
        $out = [];
        $seen = [];
        $push = function (string $title, string $url, ?string $path) use (&$out, &$seen) {
            if (isset($seen[$url.$title])) {
                return;
            }
            $seen[$url.$title] = true;
            $out[] = ['title' => $title, 'url' => $url, 'secondary' => $path];
        };

        // Menu: grupa (Pulpit, Witryna, Treści…) → bloki z nagłówkiem → pozycje → podpozycje.
        foreach (AdminMenu::for($user) as $group) {
            foreach ($group['blocks'] ?? [['heading' => null, 'items' => $group['items'] ?? []]] as $block) {
                $path = collect([$group['label'] ?? null, $block['heading'] ?? null])->filter()->implode(' › ');
                foreach ($block['items'] ?? [] as $item) {
                    $push($item['label'], $item['url'], $path ?: null);
                    foreach ($item['children'] ?? [] as $child) {
                        $push($child['label'], $child['url'], collect([$item['label'] ?? null, $child['group'] ?? null])->filter()->implode(' › '));
                    }
                }
            }
        }

        return $out;
    }

    /** Fragment treści wokół trafienia (bez HTML), np. „…zapisy na szkolenie od…”. */
    private function snippet(string $html, string $q): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
        $pos = mb_stripos($text, $q);
        if ($pos === false) {
            return Str::limit($text, 90);
        }
        $start = max(0, $pos - 40);

        return ($start > 0 ? '…' : '').mb_substr($text, $start, 100).(mb_strlen($text) > $start + 100 ? '…' : '');
    }
}
