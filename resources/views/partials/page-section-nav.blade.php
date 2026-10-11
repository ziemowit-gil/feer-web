{{--
    Blok „W tym dziale" na końcu treści strony.

      • Strona działu (ma opublikowane podstrony): siatka kart podstron
        z tytułem, krótkim opisem i ikoną typu treści.
      • Podstrona działu: nawigacja „Poprzednia / Następna" po rodzeństwie
        w kolejności z panelu oraz link powrotu do strony działu.

    Zmienna: $page. Kolejność i zestaw podstron jak w bocznym drzewie
    (Page::publishedChildren / menuSiblings).
--}}
@php
    $sectionChildren = $page->publishedChildren;
    $typeIcons = \App\Models\Page::TYPE_ICONS;
    $excerptOf = function (\App\Models\Page $p): string {
        $text = trim((string) $p->meta_description);
        if ($text === '') {
            $td = $p->typeData();
            $text = trim((string) ($td['lead'] ?? ''));
        }
        if ($text === '') {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', (string) $p->content))));
        }
        return \Illuminate\Support\Str::limit($text, 140);
    };

    $prev = $next = $sectionParent = null;
    if ($sectionChildren->isEmpty() && ($page->parent_id || $page->project_id)) {
        $siblings = $page->menuSiblings()->values();
        $index = $siblings->search(fn ($s) => $s->is($page));
        if ($index !== false) {
            $prev = $siblings->get($index - 1);
            $next = $siblings->get($index + 1);
        }
        $sectionParent = $page->project
            ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)]
            : ($page->parent ? ['label' => $page->parent->title, 'url' => $page->parent->publicUrl()] : null);
    }
@endphp

@if ($sectionChildren->isNotEmpty())
    {{-- Płasko: szare karty bez ramek, cała karta klikalna (stretched-link), fokus widoczny pierścieniem. --}}
    <section class="mt-12" aria-labelledby="section-children-heading">
        <h2 id="section-children-heading" class="text-2xl font-bold text-ink">W tym dziale</h2>
        <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="grid gap-3 sm:grid-cols-2">
            @foreach ($sectionChildren as $child)
                <li class="group relative flex gap-4 rounded-md bg-gray-50 p-4 transition hover:bg-brand-light focus-within:ring-2 focus-within:ring-brand-dark">
                    <i class="fa-solid {{ $typeIcons[$child->type] ?? 'fa-file-lines' }} mt-1 w-5 flex-none text-center text-lg text-brand-dark" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">
                        <a href="{{ $child->publicUrl() }}" class="stretched-link text-base font-bold text-brand-dark underline underline-offset-4 group-hover:no-underline focus-visible:outline-none">{{ $child->title }}</a>
                        @php $excerpt = $excerptOf($child); @endphp
                        @if ($excerpt !== '')
                            <span class="mt-1 block text-sm leading-snug text-muted">{{ $excerpt }}</span>
                        @endif
                        @if ($child->publishedChildren->isNotEmpty())
                            <span class="mt-2 block text-sm font-bold text-ink">{{ $child->publishedChildren->count() }} {{ trans_choice('podstrona|podstrony|podstron', $child->publishedChildren->count()) }}</span>
                        @endif
                    </span>
                    <i class="fa-solid fa-chevron-right mt-1.5 flex-none text-xs text-brand-dark" aria-hidden="true"></i>
                </li>
            @endforeach
        </ul>
    </section>
@elseif ($prev || $next || $sectionParent)
    <nav class="mt-12" aria-label="Nawigacja w dziale">
        @if ($sectionParent)
            <p class="mb-4 text-sm text-muted">
                <i class="fa-solid fa-folder-open mr-1 text-brand-dark" aria-hidden="true"></i>
                W tym dziale: <a href="{{ $sectionParent['url'] }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $sectionParent['label'] }}</a>
            </p>
        @endif
        <div class="grid gap-3 sm:grid-cols-2">
            @if ($prev)
                <a href="{{ $prev->publicUrl() }}" rel="prev" class="group flex min-h-16 items-center gap-3 rounded-md bg-gray-50 px-4 py-3 transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-arrow-left flex-none text-brand-dark" aria-hidden="true"></i>
                    <span class="min-w-0"><span class="block text-xs font-bold uppercase tracking-wide text-muted">Poprzednia</span><span class="block truncate font-bold text-brand-dark underline underline-offset-4 group-hover:no-underline">{{ $prev->title }}</span></span>
                </a>
            @else
                <span aria-hidden="true"></span>
            @endif
            @if ($next)
                <a href="{{ $next->publicUrl() }}" rel="next" class="group flex min-h-16 items-center justify-end gap-3 rounded-md bg-gray-50 px-4 py-3 text-right transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <span class="min-w-0"><span class="block text-xs font-bold uppercase tracking-wide text-muted">Następna</span><span class="block truncate font-bold text-brand-dark underline underline-offset-4 group-hover:no-underline">{{ $next->title }}</span></span>
                    <i class="fa-solid fa-arrow-right flex-none text-brand-dark" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </nav>
@endif
