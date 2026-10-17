<?php

namespace App\Support;

use App\Models\Banner;
use App\Models\BannerZone;
use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\FeerBand;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\TileSet;

/**
 * Elementy z menu „Wstaw" klasycznego edytora (TinyMCE / CKEditor), wspólne
 * z edytorem blokowym — żeby oba edytory pozwalały wstawić to samo:
 * gotowe wstawki HTML (BIP, sekcje akcentu, ramka z wydarzeniem) oraz moduły
 * wstawiane shortcodem (formularz, zestaw kafelków, blok z kreatora, siatka
 * kafelków, pasek, klauzule RODO, nabory, zapytania ofertowe).
 */
class ClassicInserts
{
    /** Wstawki HTML z menu „Wstaw" (bez końcowego pustego akapitu — dokłada go klasyczny edytor). */
    public const SNIPPETS = [
        'bip' => [
            'title' => 'Więcej informacji w BIP',
            'desc' => 'Ramka z logo BIP i przyciskiem do Biuletynu.',
            'html' => '<div class="bip-link-box"><p><img src="/img/bip-logo.svg" alt="Logo Biuletynu Informacji Publicznej (BIP)"></p><p>Więcej informacji o naszej fundacji oraz dokumenty formalne publikujemy w Biuletynie Informacji Publicznej.</p><p><a href="/bip" class="cta-button">Więcej informacji w BIP</a></p></div>',
        ],
        'accentLeft' => [
            'title' => 'Sekcja akcentu (lewo)',
            'desc' => 'Kolorowa sekcja z akcentem po lewej.',
            'html' => '<div class="accent-section accent-left"><p>Treść w kolorowej sekcji&hellip;</p></div>',
        ],
        'accentRight' => [
            'title' => 'Sekcja akcentu (prawo)',
            'desc' => 'Kolorowa sekcja z akcentem po prawej.',
            'html' => '<div class="accent-section accent-right"><p>Treść w kolorowej sekcji&hellip;</p></div>',
        ],
    ];

    /** Ramka z wydarzeniem (TinyMCE i edytor blokowy) albo jej wariant na <blockquote> (CKEditor rozbija nieznane <div>). */
    public static function eventBox(Event $e, bool $forCkEditor = false): string
    {
        $meta = collect([
            $e->typeLabel(),
            $e->starts_at ? $e->dateRangeLabel() : null,
            $e->location ?: $e->modeLabel(),
        ])->filter()->map(fn ($p) => e($p))->implode(' · ');
        $url = '/wydarzenia/'.e($e->slug);

        if ($forCkEditor) {
            return '<blockquote><p><strong>'.e($e->title).'</strong><br>'.$meta.'</p>'
                .'<p><a href="'.$url.'">Szczegóły wydarzenia →</a></p></blockquote>';
        }

        return '<div class="event-box"><p class="event-box-meta">'.$meta.'</p>'
            .'<p class="event-box-title"><a href="'.$url.'">'.e($e->title).'</a></p>'
            .'<p><a href="'.$url.'" class="cta-button">Szczegóły wydarzenia</a></p></div>';
    }

    /**
     * Dane dla edytora blokowego: listy do wyboru w blokach modułów i wzorce HTML.
     *
     * @return array<string, mixed>
     */
    public static function forBlockEditor(): array
    {
        $site = SiteSetting::current();

        return [
            'forms' => $site->isModuleEnabled('forms')
                ? FormDefinition::where('is_active', true)->orderBy('title')->get(['slug', 'title'])->map(fn ($f) => ['value' => $f->slug, 'label' => $f->title])->all()
                : [],
            'tileSets' => TileSet::forCurrentSite()->orderBy('name')->get(['id', 'name'])->map(fn ($t) => ['value' => $t->id, 'label' => $t->name])->all(),
            'contentBlocks' => ContentBlock::forCurrentSite()->orderBy('name')->get(['id', 'type', 'name'])
                ->map(fn ($b) => ['value' => $b->id, 'type' => $b->type, 'label' => (ContentBlock::TYPES[$b->type] ?? $b->type).': '.$b->name])->all(),
            'blockTypes' => ContentBlock::TYPES,
            'newBlockTypes' => array_keys($site->availableOptions('content_block_types', ContentBlock::TYPES)),
            'tilesGrids' => Page::where('is_published', true)->where('type', 'tiles_grid')->orderBy('title')->get(['slug', 'title'])
                ->map(fn ($p) => ['value' => $p->slug, 'label' => $p->title])->all(),
            'bands' => $site->isModuleEnabled('feer_bands')
                ? FeerBand::forCurrentSite()->active()->orderBy('order')->orderBy('title')->get(['id', 'title'])->map(fn ($b) => ['value' => $b->id, 'label' => (string) $b->title])->all()
                : [],
            // „Przycisk CTA do harmonogramu" z menu „Wstaw link".
            'schedules' => Page::where('is_published', true)->where('type', 'schedule')->orderBy('title')->get(['id', 'slug', 'title', 'type', 'parent_id'])
                ->map(fn (Page $p) => ['title' => $p->title, 'url' => '/'.$p->slug])->all(),
            'snippets' => collect(self::SNIPPETS)->map(fn ($s, $key) => ['key' => $key] + $s)->values()->all(),
            'events' => Event::where('is_published', true)->orderByDesc('starts_at')->limit(30)->get()
                ->map(fn (Event $e) => ['title' => $e->title, 'html' => self::eventBox($e)])->all(),
            // Moduł Banery: strefy ([banery:slug]) i pojedyncze banery ([baner:ID]).
            'bannerZones' => self::bannerZones(),
            'banners' => self::banners(),
            // Diagnoza modułów w bloku „Shortcode”: kod => nazwa, adres edycji, stan (szkic, pusty, wyłączony), statystyki.
            'diagnostics' => self::diagnostics(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function bannerZones(): array
    {
        return BannerZone::withCount(['banners as active_count' => fn ($q) => $q->where('is_active', true)])->orderBy('label')->get()
            ->map(fn (BannerZone $z) => ['value' => $z->slug, 'label' => $z->label, 'count' => (int) $z->active_count])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function banners(): array
    {
        return Banner::orderBy('name')->get(['id', 'name', 'is_active', 'starts_at', 'ends_at'])
            ->map(fn (Banner $b) => ['value' => $b->id, 'label' => $b->name.($b->statusLabel() !== 'aktywny' ? ' ('.$b->statusLabel().')' : '')])->all();
    }

    /**
     * Stan elementów osadzanych shortcodem — edytor pokazuje przy module link „Otwórz do edycji”,
     * informację o szkicu / wyłączeniu i (dla banerów) wyświetlenia i kliknięcia.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function diagnostics(): array
    {
        $out = [];
        foreach (Page::where('type', 'tiles_grid')->get(['id', 'slug', 'title', 'is_published', 'tiles']) as $p) {
            $tiles = collect($p->tiles ?? [])->filter(fn ($t) => filled($t['label'] ?? null) && filled($t['url'] ?? null))->count();
            $out['[kafelki:'.$p->slug.']'] = [
                'name' => $p->title, 'editUrl' => route('admin.podstrony.edit', $p),
                'state' => ! $p->is_published ? 'draft' : ($tiles === 0 ? 'empty' : 'ok'),
                'publishUrl' => ! $p->is_published ? route('admin.edytor-blokowy.publish', $p) : null,
                'note' => $tiles === 0 ? 'Siatka nie ma kafelków z nazwą i adresem.' : null,
            ];
        }
        foreach (FormDefinition::get(['id', 'slug', 'title', 'is_active']) as $f) {
            $out['[formularz:'.$f->slug.']'] = ['name' => $f->title, 'editUrl' => route('admin.formularze.edit', $f), 'state' => $f->is_active ? 'ok' : 'disabled'];
        }
        foreach (FeerBand::forCurrentSite()->get(['id', 'title', 'is_active']) as $b) {
            $out['[pasek:'.$b->id.']'] = ['name' => (string) $b->title, 'editUrl' => route('admin.feer-paski.edit', $b), 'state' => $b->is_active ? 'ok' : 'disabled'];
        }
        foreach (BannerZone::with(['banners' => fn ($q) => $q->select('banners.id', 'is_active', 'starts_at', 'ends_at', 'impressions', 'clicks')])->get() as $z) {
            $active = $z->banners->filter(fn (Banner $b) => $b->statusLabel() === 'aktywny');
            $out['[banery:'.$z->slug.']'] = [
                'name' => $z->label, 'editUrl' => route('admin.banery.index', ['strefa' => $z->slug]),
                'state' => $active->isEmpty() ? 'empty' : 'ok',
                'note' => $active->isEmpty() ? 'Strefa nie ma teraz aktywnych banerów.' : 'Aktywne banery: '.$active->count().' (pokazywane do '.$z->max_concurrent.' naraz).',
                'stats' => ['impressions' => (int) $z->banners->sum('impressions'), 'clicks' => (int) $z->banners->sum('clicks')],
            ];
        }
        foreach (Banner::get(['id', 'name', 'is_active', 'starts_at', 'ends_at', 'impressions', 'clicks']) as $b) {
            $status = $b->statusLabel();
            $out['[baner:'.$b->id.']'] = [
                'name' => $b->name, 'editUrl' => route('admin.banery.edit', $b),
                'state' => $status === 'aktywny' ? 'ok' : 'disabled',
                'note' => $status === 'aktywny' ? null : 'Baner jest '.$status.' — nie pokazuje się na stronie.',
                'stats' => ['impressions' => (int) $b->impressions, 'clicks' => (int) $b->clicks],
            ];
        }

        return $out;
    }
}
