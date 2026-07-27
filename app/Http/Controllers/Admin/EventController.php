<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Faq;
use App\Models\News;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::orderByDesc('starts_at')->get();

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.form', [
            'event' => new Event([
                'type' => 'szkolenie',
                'mode' => 'stacjonarnie',
                'registration_cta_label' => 'Zapisz się',
            ]),
            'allFaqs' => $this->faqOptions(),
        ]);
    }

    public function store(EventRequest $request)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        $event = Event::create($data);
        $this->syncFacilitatorPhoto($request, $event);
        $this->syncFaqs($request, $event);
        $event->globalFaqs()->sync($request->input('global_faqs', []));

        return redirect()->route('admin.wydarzenia.index')->with('status', 'Wydarzenie zostało dodane.');
    }

    public function edit(Event $event)
    {
        return view('admin.events.form', ['event' => $event, 'allFaqs' => $this->faqOptions()]);
    }

    /** Globalne pytania FAQ do dopięcia (puste, gdy moduł FAQ wyłączony). */
    private function faqOptions()
    {
        return SiteSetting::current()->isModuleEnabled('faq')
            ? Faq::orderBy('category')->orderBy('order')->orderBy('id')->get()
            : collect();
    }

    public function update(EventRequest $request, Event $event)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $event->id);

        $event->update($data);
        $this->syncFacilitatorPhoto($request, $event);
        $this->syncFaqs($request, $event);
        $event->globalFaqs()->sync($request->input('global_faqs', []));

        return redirect()->route('admin.wydarzenia.index')->with('status', 'Wydarzenie zostało zaktualizowane.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('admin.wydarzenia.index')->with('status', 'Wydarzenie zostało usunięte.');
    }

    /**
     * Utwórz aktualność (News) na podstawie wydarzenia — jako szkic do
     * przejrzenia. Treść składamy z terminu, miejsca, opisu, prowadzącej
     * i linku do zapisów; zdjęcie prowadzącej (jeśli jest) kopiujemy jako
     * zdjęcie newsa. Redagujemy dalej już w edytorze aktualności.
     */
    public function toNews(Event $event)
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('news'), 404);

        $news = News::create([
            'title' => $event->title,
            'slug' => $this->uniqueNewsSlug($event->title),
            'excerpt' => $event->lead,
            'audience' => $event->audience ?: 'brand',
            'content' => $this->newsContentFromEvent($event),
            'published_at' => now(),
            'is_published' => false,
        ]);

        if ($photo = $event->getFirstMedia('facilitator_photo')) {
            $photo->copy($news, 'image');
        }

        return redirect()->route('admin.newsy.edit', $news)
            ->with('status', "Utworzono aktualność „{$news->title}” na podstawie wydarzenia. Zapisana jako szkic — sprawdź treść i opublikuj.");
    }

    /** Treść newsa (HTML) złożona z pól wydarzenia; dane użytkownika escapujemy. */
    private function newsContentFromEvent(Event $event): string
    {
        $parts = [
            '<p><strong>Termin:</strong> '.e($event->dateRangeLabel()).'</p>',
            '<p><strong>Miejsce:</strong> '.e($event->modeLabel().($event->location ? ' · '.$event->location : '')).'</p>',
        ];

        if (filled($event->description)) {
            foreach (preg_split('/\n{2,}/', trim($event->description)) as $paragraph) {
                $parts[] = '<p>'.nl2br(e(trim($paragraph))).'</p>';
            }
        }

        if ($event->hasFacilitator()) {
            $parts[] = '<h2>Prowadzący / Prowadząca</h2>';
            $line = trim($event->facilitator_name.($event->facilitator_role ? ' — '.$event->facilitator_role : ''));
            if ($line !== '') {
                $parts[] = '<p><strong>'.e($line).'</strong></p>';
            }
            if (filled($event->facilitator_bio)) {
                $parts[] = '<p>'.nl2br(e(trim($event->facilitator_bio))).'</p>';
            }
        }

        if ($event->registrationHref()) {
            $parts[] = '<p><a href="'.e($event->registrationHref()).'">'.e($event->registration_cta_label).'</a></p>';
        }

        return implode("\n", $parts);
    }

    /** Unikalny slug dla nowo tworzonej aktualności. */
    private function uniqueNewsSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'aktualnosc';
        $candidate = $slug;
        $i = 2;

        while (News::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }

    /** Znormalizowane dane z żądania (checkbox publikacji, domyślne wartości). */
    private function prepared(EventRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['order'] = $data['order'] ?? 0;
        $data['registration_cta_label'] = trim((string) ($data['registration_cta_label'] ?? '')) ?: 'Zapisz się';

        // Pliki, FAQ i pomocnicze pola obsługujemy osobno.
        unset($data['facilitator_photo'], $data['remove_facilitator_photo'], $data['faqs'], $data['global_faqs']);

        return $data;
    }

    /**
     * Zsynchronizuj FAQ wydarzenia: usuwamy dotychczasowe i zapisujemy na nowo
     * (pomijając wiersze bez pytania), zachowując kolejność z formularza.
     */
    private function syncFaqs(EventRequest $request, Event $event): void
    {
        $event->faqs()->delete();

        collect($request->input('faqs', []))
            ->map(fn ($row) => [
                'question' => trim((string) ($row['question'] ?? '')),
                'answer' => trim((string) ($row['answer'] ?? '')),
            ])
            ->filter(fn ($row) => $row['question'] !== '' && $row['answer'] !== '')
            ->values()
            ->each(fn ($row, $i) => $event->faqs()->create($row + ['order' => $i]));
    }

    /** Wgraj/usuń zdjęcie prowadzącej (kolekcja jednoplikowa). */
    private function syncFacilitatorPhoto(EventRequest $request, Event $event): void
    {
        if ($request->hasFile('facilitator_photo')) {
            $event->addMediaFromRequest('facilitator_photo')->toMediaCollection('facilitator_photo');
        } elseif ($request->boolean('remove_facilitator_photo')) {
            $event->clearMediaCollection('facilitator_photo');
        }
    }

    /** Unikalny slug wydarzenia (pomijając bieżące przy edycji). */
    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'wydarzenie';
        $candidate = $slug;
        $i = 2;

        while (Event::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
