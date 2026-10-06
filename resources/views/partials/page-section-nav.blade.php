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
    <section class="mt-12 border-t border-gray-200 pt-8" aria-labelledby="section-children-heading">
        <h2 id="section-children-heading" class="mb-5 text-xl font-bold text-ink">
            <i class="fa-solid fa-folder-open mr-1.5 text-brand" aria-hidden="true"></i>W tym dziale
        </h2>
        <ul role="list" class="grid gap-4 sm:grid-cols-2">
            @foreach ($sectionChildren as $child)
                <li class="relative flex gap-4 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-brand hover:shadow-md focus-within:border-brand focus-within:ring-2 focus-within:ring-brand">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid {{ $typeIcons[$child->type] ?? 'fa-file-lines' }}"></i>
                    </span>
                    <span class="min-w-0">
                        <a href="{{ $child->publicUrl() }}" class="stretched-link text-base font-bold text-ink hover:text-brand focus-visible:outline-none">{{ $child->title }}</a>
                        @php $excerpt = $excerptOf($child); @endphp
                        @if ($excerpt !== '')
                            <span class="mt-1 block text-sm leading-snug text-muted">{{ $excerpt }}</span>
                        @endif
                        @if ($child->publishedChildren->isNotEmpty())
                            <span class="mt-2 block text-xs font-semibold text-brand">{{ $child->publishedChildren->count() }} {{ trans_choice('podstrona|podstrony|podstron', $child->publishedChildren->count()) }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
@elseif ($prev || $next || $sectionParent)
    <nav class="mt-12 border-t border-gray-200 pt-6" aria-label="Nawigacja w dziale">
        @if ($sectionParent)
            <p class="mb-4 text-sm text-muted">
                <i class="fa-solid fa-folder-open mr-1 text-brand" aria-hidden="true"></i>
                W tym dziale: <a href="{{ $sectionParent['url'] }}" class="font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $sectionParent['label'] }}</a>
            </p>
        @endif
        <div class="grid gap-3 sm:grid-cols-2">
            @if ($prev)
                <a href="{{ $prev->publicUrl() }}" rel="prev" class="group flex min-h-16 items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-arrow-left flex-none text-brand" aria-hidden="true"></i>
                    <span class="min-w-0"><span class="block text-xs font-bold uppercase tracking-wide text-muted">Poprzednia</span><span class="block truncate font-semibold text-ink group-hover:text-brand">{{ $prev->title }}</span></span>
                </a>
            @else
                <span aria-hidden="true"></span>
            @endif
            @if ($next)
                <a href="{{ $next->publicUrl() }}" rel="next" class="group flex min-h-16 items-center justify-end gap-3 rounded-xl border border-gray-200 px-4 py-3 text-right transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <span class="min-w-0"><span class="block text-xs font-bold uppercase tracking-wide text-muted">Następna</span><span class="block truncate font-semibold text-ink group-hover:text-brand">{{ $next->title }}</span></span>
                    <i class="fa-solid fa-arrow-right flex-none text-brand" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </nav>
@endif
