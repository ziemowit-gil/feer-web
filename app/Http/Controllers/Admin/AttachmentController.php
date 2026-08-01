<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function storeForPage(Request $request, Page $page)
    {
        $this->storeFor($request, $page);

        return redirect()->route('admin.podstrony.edit', $page)->with('status', 'Plik został dodany.');
    }

    public function storeForNews(Request $request, News $news)
    {
        $this->storeFor($request, $news);

        return redirect()->route('admin.newsy.edit', $news)->with('status', 'Plik został dodany.');
    }

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

    public function destroy(Attachment $attachment)
    {
        $attachment->delete();

        return redirect()->back()->with('status', 'Plik został usunięty.');
    }

    private function storeFor(Request $request, Page|News $attachable): void
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $attachment = $attachable->attachments()->create([
            'label' => $data['label'],
            'order' => $data['order'] ?? 0,
        ]);

        $attachment->addMediaFromRequest('file')->toMediaCollection('file');
    }
}
