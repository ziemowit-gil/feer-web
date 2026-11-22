<?php

namespace App\Models;

use App\Support\KreatywniTexts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Dokument z mikro modułu „Dokumenty – Kreatywni” (panel: Treści → Strona główna → Dokumenty – Kreatywni).
 * Lista w sekcji #dokumenty na stronie „O nas” szablonu „kreatywni”. Dokument prowadzi do pliku (kolekcja mediów
 * „file”), a bez pliku — do wskazanej strony serwisu (gdy jest opublikowana) albo do podanego adresu. Dokument
 * bez żadnego z nich (np. szkic strony) nie pokazuje się na stronie, dopóki redakcja go nie uzupełni.
 */
class KreatywniDocument extends Model implements HasMedia
{
    use InteractsWithMedia;
    use Concerns\BelongsToSite;
    use Concerns\LogsActivity;

    protected $table = 'kreatywni_documents';

    protected $fillable = ['site_id', 'title', 'description', 'page_id', 'url', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'page_id' => 'integer',
    ];

    /** Typy plików przyjmowane w panelu (rozszerzenia) i limit w KB. */
    public const FILE_MIMES = ['pdf', 'doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'txt'];

    public const FILE_MAX_KB = 20480;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    /** Czy tabela modułu istnieje (push wdraża kod przed migracją). */
    public static function supported(): bool
    {
        return once(fn () => Schema::hasTable('kreatywni_documents'));
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->withoutGlobalScopes();
    }

    public function fileUrl(): ?string
    {
        return $this->getFirstMediaUrl('file') ?: null;
    }

    /** „PDF, 240 KB” — typ i rozmiar pliku do opisu linku (null, gdy dokument nie ma pliku). */
    public function fileMeta(): ?string
    {
        $media = $this->getFirstMedia('file');
        if (! $media) {
            return null;
        }
        $ext = strtoupper(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));

        return trim($ext.', '.str_replace('.', ',', (string) $media->human_readable_size), ', ');
    }

    /** Opublikowana strona, do której prowadzi dokument bez pliku (null, gdy brak albo to szkic). */
    public function livePage(): ?Page
    {
        $page = $this->page_id ? $this->page : null;

        return $page && ! $page->trashed() && $page->isLiveNow() ? $page : null;
    }

    /**
     * Dokąd prowadzi dokument: plik → opublikowana strona → adres. null = dokument nie pokazuje się na stronie.
     *
     * @return array{url: string, kind: string, meta: ?string}|null
     */
    public function target(): ?array
    {
        if ($url = $this->fileUrl()) {
            return ['url' => $url, 'kind' => 'file', 'meta' => $this->fileMeta()];
        }
        if ($page = $this->livePage()) {
            return ['url' => $page->publicUrl(), 'kind' => 'page', 'meta' => null];
        }
        if ($href = KreatywniTexts::href((string) $this->url)) {
            return ['url' => $href, 'kind' => 'link', 'meta' => null];
        }

        return null;
    }

    /**
     * Dokumenty do sekcji na stronie „O nas”: widoczne dokumenty bieżącej witryny, które mają plik, opublikowaną stronę
     * albo adres, w kolejności z panelu.
     *
     * @return list<array{title: string, description: string, url: string, kind: string, meta: ?string}>
     */
    public static function forDisplay(): array
    {
        if (! self::supported()) {
            return [];
        }

        return self::forCurrentSite()->where('is_active', true)->orderBy('order')->orderBy('id')->with('media', 'page')->get()
            ->map(fn (self $d) => ($t = $d->target()) ? ['title' => $d->title, 'description' => (string) $d->description] + $t : null)
            ->filter()->values()->all();
    }

    /**
     * Strony dokumentów do przeniesienia: podstrony strony „Fundacja” witryny (tak zakłada je KreatywniSiteSeeder).
     *
     * @return \Illuminate\Support\Collection<int, Page>
     */
    public static function sourcePages(SiteSetting $site): \Illuminate\Support\Collection
    {
        $foundation = Page::withoutGlobalScopes()->where('site_id', $site->getKey())->whereNull('parent_id')
            ->where(fn ($q) => $q->where('title', 'Fundacja')->orWhere('slug', 'fundacja'))->orderBy('id')->first();
        if (! $foundation) {
            return collect();
        }

        return Page::withoutGlobalScopes()->where('site_id', $site->getKey())->where('parent_id', $foundation->getKey())
            ->whereNull('deleted_at')->orderBy('order')->orderBy('id')->get();
    }

    /**
     * Przenosi dokumenty ze stron (podstron „Fundacji”) do pustego modułu witryny: tytuł, powiązanie ze stroną i pierwszy
     * załączony plik. Zwraca liczbę dodanych dokumentów (0, gdy moduł ma już dokumenty).
     */
    public static function importFromPages(SiteSetting $site): int
    {
        if (! self::supported() || self::withoutGlobalScopes()->where('site_id', $site->getKey())->exists()) {
            return 0;
        }

        $count = 0;
        foreach (self::sourcePages($site) as $i => $page) {
            $document = self::withoutGlobalScopes()->create([
                'site_id' => $site->getKey(), 'title' => $page->title, 'page_id' => $page->getKey(),
                'order' => $i + 1, 'is_active' => true,
            ]);
            $attachment = $page->attachments()->get()->first(fn ($a) => $a->getFirstMedia('file') !== null);
            if ($attachment) {
                $attachment->getFirstMedia('file')->copy($document, 'file');
            }
            $count++;
        }

        return $count;
    }
}
