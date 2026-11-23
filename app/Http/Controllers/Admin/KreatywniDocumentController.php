<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KreatywniDocument;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mikro moduł „Dokumenty – Kreatywni” (szablon „kreatywni”): dokumenty fundacji na stronie „O nas” — dodawanie
 * z plikiem, stroną serwisu albo adresem, edycja, widoczność, kolejność i jednorazowe przeniesienie dokumentów ze
 * stron (podstron „Fundacji”). Tylko dla witryny w szablonie „kreatywni”.
 */
class KreatywniDocumentController extends Controller
{
    public function index(): View
    {
        $site = $this->site();
        $documents = KreatywniDocument::forCurrentSite()->orderBy('order')->orderBy('id')->with('media', 'page')->get();
        $importable = $documents->isEmpty() ? KreatywniDocument::sourcePages($site)->count() : 0;
        $aboutUrl = Page::forCurrentSite()->where('slug', 'like', 'o-nas%')->orderBy('id')->first()?->publicUrl();

        return view('admin.kreatywni-documents.index', compact('documents', 'importable', 'aboutUrl'));
    }

    public function create(): View
    {
        $this->site();

        return view('admin.kreatywni-documents.form', ['document' => new KreatywniDocument(['is_active' => true]), 'pages' => $this->pageOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->site();
        $document = KreatywniDocument::create($this->validated($request) + ['order' => (int) KreatywniDocument::forCurrentSite()->max('order') + 1]);
        $this->saveFile($request, $document);

        return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', 'Dodano dokument „'.$document->title.'”.');
    }

    public function edit(KreatywniDocument $document): View
    {
        $this->guard($document);

        return view('admin.kreatywni-documents.form', ['document' => $document, 'pages' => $this->pageOptions()]);
    }

    public function update(Request $request, KreatywniDocument $document): RedirectResponse
    {
        $this->guard($document);
        $document->update($this->validated($request));
        $this->saveFile($request, $document);

        return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', 'Zapisano dokument „'.$document->title.'”.');
    }

    public function destroy(KreatywniDocument $document): RedirectResponse
    {
        $this->guard($document);
        $title = $document->title;
        $document->delete();

        return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', 'Usunięto dokument „'.$title.'”.');
    }

    /** Wyżej / Niżej: zamiana miejscami z sąsiednim dokumentem (kolejność najpierw porządkowana 1, 2, 3…). */
    public function move(Request $request, KreatywniDocument $document): RedirectResponse
    {
        $this->guard($document);
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($document, $direction) {
            $ids = KreatywniDocument::forCurrentSite()->orderBy('order')->orderBy('id')->pluck('id')->all();
            $at = array_search($document->getKey(), $ids, true);
            $to = $direction === 'up' ? $at - 1 : $at + 1;
            if ($at === false || ! isset($ids[$to])) {
                return;
            }
            [$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
            foreach ($ids as $i => $id) {
                KreatywniDocument::whereKey($id)->update(['order' => $i + 1]);
            }
        });

        return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', 'Przesunięto dokument „'.$document->title.'” '.($direction === 'up' ? 'wyżej' : 'niżej').'.');
    }

    /** Przenosi dokumenty ze stron (podstron „Fundacji”) do pustego modułu: tytuł, powiązana strona i załączony plik. */
    public function import(): RedirectResponse
    {
        $site = $this->site();
        if (KreatywniDocument::forCurrentSite()->exists()) {
            return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', 'Moduł ma już dokumenty — import pominięty.');
        }
        $count = KreatywniDocument::importFromPages($site);

        return redirect()->route('admin.kreatywni-dokumenty.index')->with('status', $count > 0
            ? 'Przeniesiono ze stron: '.$count.' '.trans_choice('dokument|dokumenty|dokumentów', $count).'. Dokument bez pliku jest na stronie „O nas” jako „w przygotowaniu”, dopóki nie dodasz pliku albo nie opublikujesz jego strony.'
            : 'Nie znaleziono stron dokumentów (podstron strony „Fundacja”) do przeniesienia.');
    }

    /** @return Collection<int, Page> strony witryny do wyboru (bez folderów systemowych i stron osób) */
    private function pageOptions(): Collection
    {
        return Page::forCurrentSite()->whereNotIn('type', ['about_person', 'sys_folder'])->orderBy('title')->get(['id', 'title', 'is_published', 'parent_id']);
    }

    private function site(): SiteSetting
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniDocument::supported(), 404);

        return $site;
    }

    private function guard(KreatywniDocument $document): void
    {
        abort_unless((int) $document->site_id === (int) $this->site()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $siteId = SiteSetting::current()->getKey();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:'.implode(',', KreatywniDocument::FILE_MIMES), 'max:'.KreatywniDocument::FILE_MAX_KB],
            'remove_file' => ['nullable', 'boolean'],
            'page_id' => ['nullable', 'integer', Rule::exists('pages', 'id')->where('site_id', $siteId)->whereNull('deleted_at')],
            'url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && KreatywniTexts::href((string) $value) === null) {
                    $fail('Adres musi zaczynać się od https://, http:// albo „/” (strona w witrynie).');
                }
            }],
        ], [], [
            'title' => 'nazwa dokumentu', 'description' => 'opis', 'file' => 'plik', 'page_id' => 'strona', 'url' => 'adres',
        ]);

        return [
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'page_id' => ($data['page_id'] ?? null) ?: null,
            'url' => filled($data['url'] ?? null) ? trim($data['url']) : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function saveFile(Request $request, KreatywniDocument $document): void
    {
        if ($request->hasFile('file')) {
            $document->addMediaFromRequest('file')->toMediaCollection('file');
        } elseif ($request->boolean('remove_file')) {
            $document->clearMediaCollection('file');
        }
    }
}
