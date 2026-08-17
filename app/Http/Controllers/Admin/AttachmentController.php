<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\JobOffer;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Request;

/**
 * Panel admin: zarządzanie załącznikami (plikami do pobrania) przypiętymi do podstron i aktualności.
 *
 * Metody: storeForPage(), storeForNews(), lista(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AttachmentController extends Controller
{
    /** Dodaje załącznik do podstrony. */
    public function storeForPage(Request $request, Page $page)
    {
        // Pliki identyfikacji wizualnej (CDR, AI) mogą być duże — limit 100 MB.
        $maxKb = $page->isBrandAssets() ? 102400 : 10240;
        $this->storeFor($request, $page, $maxKb);

        return redirect()->route('admin.podstrony.edit', $page)->with('status', 'Plik został dodany.');
    }

    /** Dodaje załącznik do aktualności. */
    public function storeForNews(Request $request, News $news)
    {
        $this->storeFor($request, $news);

        return redirect()->route('admin.newsy.edit', $news)->with('status', 'Plik został dodany.');
    }

    /** Dodaje załącznik do ogłoszenia o pracę. */
    public function storeForJobOffer(Request $request, JobOffer $praca)
    {
        $this->storeFor($request, $praca);

        return redirect()->route('admin.praca.edit', $praca)->with('status', 'Plik został dodany.');
    }

    /**
     * Zwraca JSON z listą wszystkich załączników z metadanymi (dla pickera w edytorze).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lista()
    {
        $attachments = Attachment::query()
            ->with('media')
            ->orderBy('label')
            ->get()
            ->filter(fn ($a) => $a->file_url)
            ->map(function ($a) {
                $owner = $a->attachable;

                return [
                    'id' => $a->id,
                    'label' => $a->label,
                    'url' => $a->file_url,
                    'extension' => $a->file_extension,
                    'size' => $a->file_size,
                    'owner_title' => $owner?->title ?? null,
                ];
            });

        return response()->json($attachments->values());
    }

    /** Usuwa załącznik wraz z plikiem. */
    public function destroy(Attachment $attachment)
    {
        $attachment->delete();

        return redirect()->back()->with('status', 'Plik został usunięty.');
    }

    private function storeFor(Request $request, Page|News|JobOffer $attachable, int $maxKb = 10240): void
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:120'],
            'file'  => ['required', 'file', 'max:' . $maxKb],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $attachment = $attachable->attachments()->create([
            'label' => $data['label'],
            'group' => $data['group'] ?? null,
            'order' => $data['order'] ?? 0,
        ]);

        $attachment->addMediaFromRequest('file')->toMediaCollection('file');
    }
}
