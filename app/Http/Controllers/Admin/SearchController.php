<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ['projects', Project::class, 'title', 'admin.projekty.edit', 'Projekt', 'fa-diagram-project', null],
        ['events', Event::class, 'title', 'admin.wydarzenia.edit', 'Wydarzenie', 'fa-calendar-days', null],
        ['partners', Partner::class, 'name', 'admin.partnerzy.edit', 'Partner', 'fa-handshake', null],
        ['faq', Faq::class, 'question', 'admin.faq.edit', 'FAQ', 'fa-circle-question', null],
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

        foreach (self::CONTENT as [$module, $class, $field, $route, $label, $icon, $where]) {
            if (! $user->canAccessModule($module)) {
                continue;
            }

            $query = $class::where($field, 'like', '%' . $q . '%');
            if ($where !== null) {
                $query->where($where[0], $where[1], $where[2]);
            }

            foreach ($query->limit(5)->get() as $model) {
                $secondary = null;
                if ($label === 'Osoba') {
                    $secondary = $model->person_role;
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

        // Sekcje panelu pasujące do frazy.
        foreach ($this->sections($user) as $section) {
            if (mb_stripos($section['title'], $q) !== false) {
                $results[] = $section + ['label' => 'Sekcja', 'icon' => 'fa-compass'];
            }
        }

        return response()->json(['results' => array_slice($results, 0, 30)]);
    }

    /** Statyczne cele nawigacji w panelu (z poszanowaniem uprawnień). */
    private function sections(User $user): array
    {
        $out = [];
        $add = fn (string $title, string $route) => $out[] = ['title' => $title, 'url' => route($route)];

        if ($user->canAccessModule('pages')) {
            $add('Strony', 'admin.podstrony.index');
            $add('Osoby', 'admin.osoby.index');
        }
        if ($user->canAccessModule('news')) {
            $add('Aktualności', 'admin.newsy.index');
        }
        if ($user->canAccessModule('projects')) {
            $add('Projekty', 'admin.projekty.index');
        }
        if ($user->canAccessModule('events')) {
            $add('Szkolenia i wydarzenia', 'admin.wydarzenia.index');
        }

        if ($user->isAdmin()) {
            $add('Ustawienia strony', 'admin.ustawienia.edit');
            $add('Użytkownicy', 'admin.uzytkownicy.index');
            $add('Kosz', 'admin.kosz.index');
            $add('Dziennik zdarzeń', 'admin.dziennik.index');
            $add('Multimedia', 'admin.multimedia.index');
        }

        return $out;
    }
}
