<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

/**
 * Panel admin: dedykowany edytor osi czasu (historii) strony „O organizacji" —
 * wyodrębniona z dużego formularza strony, żeby ułatwić częste aktualizacje.
 *
 * Metody: edit(), update().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class TimelineController extends Controller
{
    /**
     * Kolumny pojedynczego wpisu osi czasu — współdzielone przez walidację
     * i „sprzątanie" wierszy, żeby oba nie rozjechały się z formularzem strony.
     */
    private const ROW_KEYS = ['year', 'text', 'url', 'label', 'url2', 'label2', 'url3', 'label3', 'color'];

    /** Wyświetla edytor osi czasu dla wybranej strony „O organizacji". */
    public function edit(Request $request)
    {
        $pages = Page::where('type', 'about')->orderBy('order')->orderBy('title')->get();

        if ($pages->isEmpty()) {
            return view('admin.timeline.edit', ['page' => null, 'pages' => $pages]);
        }

        $page = $pages->firstWhere('id', (int) $request->query('page')) ?? $pages->first();

        return view('admin.timeline.edit', compact('page', 'pages'));
    }

    /** Zapisuje zaktualizowaną oś czasu (wiersze puste są pomijane). */
    public function update(Request $request, Page $page)
    {
        abort_unless($page->isAbout(), 404);

        $request->validate([
            'about_timeline' => ['nullable', 'array'],
            'about_timeline.*.year' => ['nullable', 'string', 'max:20'],
            'about_timeline.*.text' => ['nullable', 'string', 'max:500'],
            'about_timeline.*.url' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.url2' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label2' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.url3' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label3' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.color' => ['nullable', 'string', 'max:7'],
        ]);

        $page->update(['about_timeline' => $this->compactRows($request->input('about_timeline', []))]);

        return redirect()
            ->route('admin.os-czasu.edit', ['page' => $page->id])
            ->with('status', 'Oś czasu została zaktualizowana.');
    }

    /**
     * Przytnij wiersze do znanych kolumn i pomiń te całkiem puste, żeby kolumna
     * JSON pozostała czysta. Zwraca null, gdy nie zostaje żaden wpis.
     */
    private function compactRows($rows): ?array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            $clean = [];
            foreach (self::ROW_KEYS as $key) {
                $clean[$key] = trim((string) ($row[$key] ?? ''));
            }
            if (implode('', $clean) !== '') {
                $out[] = $clean;
            }
        }

        return $out ?: null;
    }
}
