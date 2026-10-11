@extends('admin.layout')

@section('title', 'Wiem FEER — artykuły')

{{--
    Lista artykułów Wiem FEER: wyszukiwarka i filtry (GET — adres do udostępnienia, działa
    bez JS), podsumowanie aktywnych filtrów z linkami „usuń", tabela z sortowaniem
    (aria-sort), stronicowanie 25/50/100 i akcje zbiorcze. Filtry: Support\ArticleListFilters.
--}}

@use('Modules\Blog\Support\ArticleListFilters', 'F')
@php
    $v = $filters->values;
    $input = 'w-full rounded border-gray-400 text-sm text-ink focus:border-brand-dark focus:ring-brand-dark';
    $label = 'mb-1 block text-sm font-bold text-ink';
    $link = 'font-bold text-brand-dark underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btnPrimary = 'inline-flex min-h-10 items-center gap-2 rounded bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $icon = 'inline-flex h-9 w-9 items-center justify-center rounded text-ink hover:bg-brand-light hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $total = $articles->total();
    $plural = fn (int $n) => $n === 1 ? 'artykuł' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'artykuły' : 'artykułów');
    $summary = $filters->summary();
    $fmtDate = fn ($d) => $d?->format('d.m.Y H:i');
@endphp

@section('content')
<style>
    .blog-list :is(input, select, button, a, [tabindex]):focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
    .blog-list :is(input, select):user-invalid { box-shadow: none; }
</style>

<div class="blog-list space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-4 text-sm" aria-label="Wiem FEER — inne sekcje">
            <a href="{{ route('admin.komentarze-bloga.index') }}" class="{{ $link }}"><i class="fa-solid fa-comments mr-1" aria-hidden="true"></i>Moderacja komentarzy</a>
            <a href="{{ route('admin.kategorie-bloga.index') }}" class="{{ $link }}"><i class="fa-solid fa-folder mr-1" aria-hidden="true"></i>Kategorie</a>
            <a href="{{ route('admin.autorzy-bloga.index') }}" class="{{ $link }}"><i class="fa-solid fa-user-pen mr-1" aria-hidden="true"></i>Autorzy</a>
        </nav>
        <a href="{{ route('admin.wiem-feer.create') }}" class="{{ $btnPrimary }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj artykuł</a>
    </div>

    {{-- ==================== FILTRY ==================== --}}
    <form method="GET" action="{{ route('admin.wiem-feer.index') }}" role="search" aria-label="Wyszukiwanie i filtry artykułów" class="space-y-4 rounded-md bg-white p-5">
        @if ($v['sort'] !== '')
            <input type="hidden" name="sort" value="{{ $v['sort'] }}"><input type="hidden" name="dir" value="{{ $v['dir'] }}">
        @endif
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="f-q" class="{{ $label }}">Szukaj</label>
                <input type="search" id="f-q" name="q" value="{{ $v['q'] }}" maxlength="100" placeholder="tytuł, adres, zajawka lub treść" class="{{ $input }}">
            </div>
            <div>
                <label for="f-status" class="{{ $label }}">Status</label>
                <select id="f-status" name="status" class="{{ $input }}">
                    <option value="">Wszystkie</option>
                    @foreach (F::STATUSES as $value => $text)
                        <option value="{{ $value }}" @selected($v['status'] === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-kategoria" class="{{ $label }}">Kategoria</label>
                <select id="f-kategoria" name="kategoria" class="{{ $input }}">
                    <option value="">Wszystkie</option>
                    <option value="brak" @selected($v['kategoria'] === 'brak')>— bez kategorii —</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected($v['kategoria'] === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-autor" class="{{ $label }}">Autor</label>
                <select id="f-autor" name="autor" class="{{ $input }}">
                    <option value="">Wszyscy</option>
                    @foreach ($authors as $a)
                        <option value="{{ $a->id }}" @selected($v['autor'] === (string) $a->id)>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-tag" class="{{ $label }}">Tag</label>
                <select id="f-tag" name="tag" class="{{ $input }}">
                    <option value="">Wszystkie</option>
                    @foreach ($tags as $t)
                        <option value="{{ $t->slug }}" @selected($v['tag'] === $t->slug)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-format" class="{{ $label }}">Edytor treści</label>
                <select id="f-format" name="format" class="{{ $input }}">
                    <option value="">Wszystkie</option>
                    @foreach (F::FORMATS as $value => $text)
                        <option value="{{ $value }}" @selected($v['format'] === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="grid grid-cols-2 gap-2">
                <legend class="{{ $label }}">Data publikacji</legend>
                <div>
                    <label for="f-od" class="mb-1 block text-xs text-muted">od</label>
                    <input type="date" id="f-od" name="od" value="{{ $v['od'] }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="f-do" class="mb-1 block text-xs text-muted">do</label>
                    <input type="date" id="f-do" name="do" value="{{ $v['do'] }}" class="{{ $input }}">
                </div>
            </fieldset>
        </div>
        <fieldset>
            <legend class="sr-only">Dodatkowe warunki</legend>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach (F::TOGGLES as $key => $text)
                    <label class="flex min-h-9 items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($v[$key]) class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
                        {{ \Illuminate\Support\Str::ucfirst($text) }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="f-na-strone" class="{{ $label }}">Na stronie</label>
                <select id="f-na-strone" name="na_strone" class="{{ $input }} w-auto">
                    @foreach (F::PER_PAGE as $n)
                        <option value="{{ $n }}" @selected($v['na_strone'] === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="{{ $btnPrimary }}"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filtruj</button>
            @if ($filters->active())
                <a href="{{ route('admin.wiem-feer.index') }}" class="min-h-10 py-2 text-sm {{ $link }}">Wyczyść filtry</a>
            @endif
        </div>
    </form>

    {{-- ==================== PODSUMOWANIE ==================== --}}
    <div role="status" aria-live="polite" class="text-sm text-ink" id="list-summary">
        <p><span class="font-bold">Znaleziono: {{ $total }} {{ $plural($total) }}.</span>
            @if ($summary)
                Filtry:
                @foreach ($summary as $item)
                    {{ $item['label'] }}
                    (<a href="{{ $item['remove'] }}" class="{{ $link }}">usuń<span class="sr-only"> filtr: {{ $item['label'] }}</span></a>){{ $loop->last ? '.' : ',' }}
                @endforeach
            @endif
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-900" role="alert">
            <p class="font-bold">Nie wykonano akcji zbiorczej:</p>
            <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ==================== AKCJE ZBIORCZE ==================== --}}
    <form method="POST" action="{{ route('admin.wiem-feer.zbiorczo') }}" id="bulk-form" data-bulk-form
        class="flex flex-wrap items-end gap-3 rounded-md bg-gray-100 p-4" aria-label="Akcje zbiorcze">
        @csrf
        <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
        <div>
            <label for="bulk-action" class="{{ $label }}">Z zaznaczonymi</label>
            <select id="bulk-action" name="action" class="{{ $input }} w-auto" data-bulk-action>
                <option value="">— wybierz akcję —</option>
                <option value="publish">Opublikuj</option>
                <option value="draft">Cofnij do szkicu</option>
                <option value="disable">Wyłącz</option>
                <option value="enable">Włącz</option>
                <option value="category">Przypisz kategorię</option>
                <option value="delete">Usuń</option>
            </select>
        </div>
        <div>
            <label for="bulk-category" class="{{ $label }}">Kategoria <span class="font-normal text-muted">(dla „Przypisz kategorię”)</span></label>
            <select id="bulk-category" name="category_id" class="{{ $input }} w-auto">
                <option value="">—</option>
                <option value="brak">— bez kategorii —</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex min-h-10 items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="confirm_delete" value="1" class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
            Potwierdzam usunięcie (dla „Usuń”)
        </label>
        <button type="submit" class="{{ $btnPrimary }}">Wykonaj</button>
        <p class="w-full text-xs text-muted" data-bulk-count>Zaznacz artykuły w tabeli (pole wyboru w pierwszej kolumnie).</p>
    </form>

    {{-- ==================== TABELA ==================== --}}
    <div role="region" aria-labelledby="articles-caption" tabindex="0" class="overflow-x-auto rounded-md bg-white">
        <table class="w-full min-w-[64rem] text-left text-sm">
            <caption id="articles-caption" class="sr-only">
                Artykuły Wiem FEER ({{ $total }}){{ $v['sort'] !== '' ? ', sortowanie: ' . mb_strtolower(F::SORTS[$v['sort']]) . ($v['dir'] === 'asc' ? ' rosnąco' : ' malejąco') : '' }}
            </caption>
            <thead class="bg-gray-100 text-xs font-bold uppercase tracking-wide text-ink">
                <tr>
                    <th scope="col" class="w-10 px-3 py-3">
                        <input type="checkbox" form="bulk-form" data-select-all aria-label="Zaznacz wszystkie artykuły na tej stronie"
                            class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
                    </th>
                    <th scope="col" class="w-20 px-3 py-3"><span class="sr-only">Miniatura</span></th>
                    @foreach (['tytul' => 'Tytuł', 'autor' => 'Autor', 'kategoria' => 'Kategoria'] as $col => $text)
                        @include('blog::admin.partials.sort-header', ['col' => $col, 'text' => $text])
                    @endforeach
                    <th scope="col" class="px-3 py-3">Tagi</th>
                    @include('blog::admin.partials.sort-header', ['col' => 'publikacja', 'text' => 'Data publikacji'])
                    @include('blog::admin.partials.sort-header', ['col' => 'zmiana', 'text' => 'Ostatnia zmiana'])
                    @include('blog::admin.partials.sort-header', ['col' => 'komentarze', 'text' => 'Komentarze'])
                    @include('blog::admin.partials.sort-header', ['col' => 'wyswietlenia', 'text' => 'Wyświetlenia'])
                    <th scope="col" class="px-3 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    @php
                        $thumb = $article->imageUrl();
                        $st = $article->status();
                    @endphp
                    <tr class="align-top even:bg-gray-50">
                        <td class="px-3 py-3">
                            <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $article->id }}" data-row-check
                                aria-label="Zaznacz: {{ $article->title }}" class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
                        </td>
                        <td class="px-3 py-3">
                            @if ($thumb)
                                <img src="{{ $thumb }}" alt="" loading="lazy" class="h-12 w-16 rounded object-cover">
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('admin.wiem-feer.edit', $article) }}" class="font-bold text-ink hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $article->title }}</a>
                            <span class="mt-0.5 block text-xs text-muted">/{{ $article->slug }} · {{ $article->isMarkdown() ? 'Markdown' : 'HTML' }}@if ($article->is_featured) · wyróżniony @endif</span>
                        </td>
                        <td class="px-3 py-3 text-ink">{{ $article->authorLine() ?: '—' }}</td>
                        <td class="px-3 py-3 text-ink">{{ $article->category?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-ink">{{ $article->tags->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-3 py-3">
                            <span class="block font-bold text-ink">
                                @if ($st === 'scheduled')
                                    Zaplanowany na {{ $fmtDate($article->published_at) }}
                                @else
                                    {{ $article->statusLabel() }}
                                @endif
                                @if ($article->isWip()) · W przygotowaniu @endif
                            </span>
                            @if ($article->published_at && $st !== 'scheduled')
                                <span class="block text-xs text-muted">{{ $fmtDate($article->published_at) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-ink">{{ $fmtDate($article->updated_at) }}</td>
                        <td class="px-3 py-3 text-ink">
                            {{ $article->approved_comments_count }} <span class="text-muted">zatw.</span>
                            @if ($article->pending_comments_count)
                                <a href="{{ route('admin.komentarze-bloga.index') }}" class="block text-xs {{ $link }}">{{ $article->pending_comments_count }} do moderacji</a>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-ink tabular-nums">{{ number_format((int) $article->views, 0, ',', ' ') }}</td>
                        <td class="px-3 py-3">
                            <div class="flex justify-end gap-1">
                                @if ($article->isVisible())
                                    <a href="{{ route('blog.show', $article) }}" target="_blank" rel="noopener" class="{{ $icon }}" title="Zobacz na stronie">
                                        <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i><span class="sr-only">Zobacz na stronie: {{ $article->title }} (nowa karta)</span>
                                    </a>
                                @else
                                    <a href="{{ $article->previewUrl() }}" target="_blank" rel="noopener" class="{{ $icon }}" title="Podgląd wersji roboczej (link ważny 14 dni)">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">Podgląd: {{ $article->title }} (nowa karta)</span>
                                    </a>
                                @endif
                                <a href="{{ route('admin.wiem-feer.edit', $article) }}" class="{{ $icon }}" title="Edytuj">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $article->title }}</span>
                                </a>
                                <form method="POST" action="{{ route('admin.wiem-feer.klonuj', $article) }}">
                                    @csrf
                                    <button type="submit" class="{{ $icon }}" title="Klonuj jako szkic" aria-label="Klonuj jako szkic: {{ $article->title }}"><i class="fa-solid fa-copy" aria-hidden="true"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.wiem-feer.wylacz', $article) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($article->is_disabled)
                                        <button type="submit" class="{{ $icon }}" title="Włącz artykuł (jest wyłączony)" aria-label="Włącz: {{ $article->title }}"><i class="fa-solid fa-toggle-off" aria-hidden="true"></i></button>
                                    @else
                                        <button type="submit" class="{{ $icon }}" title="Wyłącz artykuł" aria-label="Wyłącz: {{ $article->title }}"><i class="fa-solid fa-toggle-on" aria-hidden="true"></i></button>
                                    @endif
                                </form>
                                <form method="POST" action="{{ route('admin.wiem-feer.destroy', $article) }}" data-confirm="Usunąć artykuł „{{ $article->title }}” wraz z komentarzami?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $icon }} hover:text-red-700" title="Usuń" aria-label="Usuń: {{ $article->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-ink">
                            @if ($filters->active())
                                Brak artykułów spełniających filtry. <a href="{{ route('admin.wiem-feer.index') }}" class="{{ $link }}">Wyczyść filtry</a>
                            @else
                                Brak artykułów. Dodaj pierwszy powyżej.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($articles->hasPages())
        <nav aria-label="Strony listy artykułów">{{ $articles->links() }}</nav>
    @endif
</div>

<script>
(function () {
    function ask(msg) {
        if (window.Alpine && Alpine.store && Alpine.store('confirm')) return Alpine.store('confirm').ask(msg);
        return Promise.resolve(window.confirm(msg));
    }
    // Potwierdzenie usunięcia pojedynczego artykułu.
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (f.dataset.confirmed) return;
            e.preventDefault();
            ask(f.dataset.confirm).then(function (ok) { if (ok) { f.dataset.confirmed = '1'; f.submit(); } });
        });
    });

    // Zaznacz wszystkie + licznik zaznaczonych.
    var all = document.querySelector('[data-select-all]');
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-check]'));
    var count = document.querySelector('[data-bulk-count]');
    function sync() {
        var n = rows.filter(function (r) { return r.checked; }).length;
        if (all) { all.checked = n > 0 && n === rows.length; all.indeterminate = n > 0 && n < rows.length; }
        if (count) count.textContent = n ? 'Zaznaczono: ' + n + '.' : 'Zaznacz artykuły w tabeli (pole wyboru w pierwszej kolumnie).';
    }
    if (all) all.addEventListener('change', function () { rows.forEach(function (r) { r.checked = all.checked; }); sync(); });
    rows.forEach(function (r) { r.addEventListener('change', sync); });

    // Potwierdzenie akcji zbiorczej.
    var bulk = document.querySelector('[data-bulk-form]');
    if (bulk) {
        bulk.addEventListener('submit', function (e) {
            if (bulk.dataset.confirmed) return;
            var sel = bulk.querySelector('[data-bulk-action]');
            var n = rows.filter(function (r) { return r.checked; }).length;
            if (!sel.value || !n) return; // walidacja po stronie serwera pokaże komunikat
            e.preventDefault();
            var text = sel.options[sel.selectedIndex].text;
            ask(text + ' — zaznaczone artykuły: ' + n + '. Kontynuować?' + (sel.value === 'delete' ? '\n\nUsunięcia nie można cofnąć.' : ''))
                .then(function (ok) { if (ok) { bulk.dataset.confirmed = '1'; bulk.submit(); } });
        });
    }
})();
</script>
@endsection
