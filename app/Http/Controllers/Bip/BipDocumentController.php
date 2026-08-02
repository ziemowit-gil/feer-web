<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Http\Requests\BipDocumentRequest;
use App\Models\BipDocument;
use Illuminate\Support\Str;

/**
 * Panel admin: CRUD dokumentów BIP (Biuletyn Informacji Publicznej).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), toggleVisibility().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BipDocumentController extends Controller
{
    /** Lista wszystkich dokumentów BIP. */
    public function index()
    {
        $documents = BipDocument::orderBy('category')->orderBy('order')->orderBy('title')
            ->with(['creator', 'updater', 'media'])
            ->get();

        return view('admin.bip.index', compact('documents'));
    }

    /** Formularz dodawania nowego dokumentu BIP. */
    public function create()
    {
        return view('admin.bip.form', ['document' => new BipDocument]);
    }

    /** Zapisuje nowy dokument BIP wraz z załącznikami. */
    public function store(BipDocumentRequest $request)
    {
        $document = BipDocument::create($this->prepare($request));
        $this->syncFiles($document, $request);

        return redirect()->route('admin.bip-dokumenty.index')
            ->with('status', 'Dokument BIP "' . $document->title . '" został dodany.');
    }

    /** Formularz edycji dokumentu BIP. */
    public function edit(BipDocument $bipDocument)
    {
        return view('admin.bip.form', ['document' => $bipDocument->load('media')]);
    }

    /** Aktualizuje dokument BIP wraz z załącznikami. */
    public function update(BipDocumentRequest $request, BipDocument $bipDocument)
    {
        $bipDocument->update($this->prepare($request, $bipDocument->id));
        $this->syncFiles($bipDocument, $request);

        return redirect()->route('admin.bip-dokumenty.index')
            ->with('status', 'Dokument BIP "' . $bipDocument->title . '" został zaktualizowany.');
    }

    /** Usuwa (soft-delete) dokument BIP — trafia do kosza. */
    public function destroy(BipDocument $bipDocument)
    {
        $title = $bipDocument->title;
        $bipDocument->delete();

        return redirect()->route('admin.bip-dokumenty.index')
            ->with('status', 'Dokument BIP "' . $title . '" został przeniesiony do kosza.');
    }

    /** Przełącza widoczność dokumentu BIP na stronie publicznej. */
    public function toggleVisibility(BipDocument $bipDocument)
    {
        $nowPublished = ! $bipDocument->is_published;

        $bipDocument->update([
            'is_published' => $nowPublished,
            'published_at' => $nowPublished ? ($bipDocument->published_at ?? now()) : $bipDocument->published_at,
        ]);

        $status = $nowPublished
            ? 'Dokument BIP "' . $bipDocument->title . '" jest teraz widoczny.'
            : 'Dokument BIP "' . $bipDocument->title . '" jest teraz ukryty.';

        return back()->with('status', $status);
    }

    /**
     * Przygotowuje dane do zapisu — generuje slug jeśli nie podano,
     * zapewnia jego unikalność w tabeli.
     */
    private function prepare(BipDocumentRequest $request, ?int $currentId = null): array
    {
        $data = $request->safe()->except(['files', 'remove_files']);
        $data['is_published'] = $request->boolean('is_published');
        $data['order'] = (int) ($data['order'] ?? 0);

        $slug = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        $baseSlug = $slug;
        $suffix = 2;

        while (
            BipDocument::where('slug', $slug)
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->whereNull('deleted_at')
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $data['slug'] = $slug;

        return $data;
    }

    /** Wgrywa nowe pliki i usuwa zaznaczone. */
    private function syncFiles(BipDocument $document, BipDocumentRequest $request): void
    {
        $removeIds = array_map('intval', (array) $request->input('remove_files', []));
        if ($removeIds !== []) {
            $document->getMedia('files')
                ->whereIn('id', $removeIds)
                ->each(fn ($media) => $media->delete());
        }

        foreach ((array) $request->file('files', []) as $file) {
            $document->addMedia($file)->toMediaCollection('files');
        }
    }
}
