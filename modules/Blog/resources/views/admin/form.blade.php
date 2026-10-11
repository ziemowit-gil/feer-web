@extends('admin.layout')

@section('title', $article->exists ? 'Edytuj artykuł' : 'Nowy artykuł')

{{--
    Edycja wpisu Wiem FEER: zakładki (WAI-ARIA tabs) — Treść, Autorzy i kategorie, Zdjęcie,
    Publikacja, SEO — z przyklejonym paskiem akcji i kolumną „Publikacja" po prawej (desktop).
    Treść: edytor Markdown (nowe wpisy) albo dotychczasowy edytor wizualny (stare wpisy HTML)
    z przełączaniem „Przełącz na Markdown" / „Wróć do edytora wizualnego".
    Po błędzie walidacji: podsumowanie błędów z linkami do pól i przejście do pierwszej
    zakładki z błędem. Nazwy pól bez zmian — kontrakt BlogArticleController::validated().
--}}

@php
    // Format treści: stare wpisy zostają przy HTML, nowe piszemy w Markdown.
    $storedFormat = $article->exists ? ($article->body_format ?: 'html') : 'markdown';
    $format = old('body_format', $storedFormat) === 'html' ? 'html' : 'markdown';
    // Edytor wizualny ładujemy tylko dla wpisów zapisanych jako HTML (można do niego wrócić).
    $hasWysiwyg = $article->exists && $storedFormat === 'html';
    $markdownValue = old('body_markdown', $article->body_markdown);
    // Wpis bez edytora wizualnego z wybranym „po zapisie HTML" (np. po błędzie walidacji) — nadal edytujemy Markdown.
    $htmlAfterSave = ! $hasWysiwyg && $format === 'html';

    $tabs = [
        'tresc'      => ['label' => 'Treść', 'icon' => 'fa-pen-to-square'],
        'autorzy'    => ['label' => 'Autorzy i kategorie', 'icon' => 'fa-user-pen'],
        'zdjecie'    => ['label' => 'Zdjęcie', 'icon' => 'fa-image'],
        'publikacja' => ['label' => 'Publikacja', 'icon' => 'fa-calendar-check'],
        'seo'        => ['label' => 'SEO', 'icon' => 'fa-magnifying-glass'],
    ];

    // Pole błędu → [zakładka, id elementu do fokusu].
    $errorTargets = [
        'title' => ['tresc', 'title'], 'slug' => ['tresc', 'slug'], 'excerpt' => ['tresc', 'excerpt'],
        'body' => ['tresc', $hasWysiwyg ? 'editor-body' : 'md-body'], 'body_markdown' => ['tresc', 'md-body'], 'body_format' => ['tresc', 'md-body'],
        'author_id' => ['autorzy', 'author_id'], 'co_authors' => ['autorzy', 'co-authors-first'],
        'author_name' => ['autorzy', 'author_name'], 'author_bio' => ['autorzy', 'author_bio'],
        'category_id' => ['autorzy', 'category_id'], 'tags' => ['autorzy', 'tags'],
        'image' => ['zdjecie', 'image-file'], 'library_media_id' => ['zdjecie', 'image-file'], 'image_alt' => ['zdjecie', 'image-alt'],
        'published_at' => ['publikacja', 'published_at'], 'is_published' => ['publikacja', 'is_published'],
        'disabled_message' => ['publikacja', 'disabled_message'], 'wip_mode' => ['publikacja', 'wip_mode-none'],
        'wip_message' => ['publikacja', 'wip_message'],
        'meta_title' => ['seo', 'meta_title'], 'meta_description' => ['seo', 'meta_description'],
    ];
    $errorList = collect($errors->getMessages())->map(function ($messages, $key) use ($errorTargets) {
        $base = explode('.', $key)[0];
        [$tab, $target] = $errorTargets[$base] ?? ['tresc', 'title'];

        return ['field' => $base, 'tab' => $tab, 'target' => $target, 'message' => $messages[0]];
    })->unique('field')->values();
    $tabsWithErrors = $errorList->pluck('tab')->unique()->all();
    $firstErrorTab = $errorList->first()['tab'] ?? null;

    $input = 'w-full rounded border-gray-400 text-ink focus:border-brand-dark focus:ring-brand-dark';
    $err = 'mt-1 text-sm font-bold text-red-700';
    $invalid = fn (string $f) => $errors->has($f) ? 'aria-invalid=true' : '';
    $btnPrimary = 'inline-flex min-h-10 items-center gap-2 rounded bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $btnSecondary = 'inline-flex min-h-10 items-center gap-2 rounded border border-gray-400 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $link = 'font-bold text-brand-dark underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';

    $publicBase = rtrim(route('blog.index'), '/') . '/';
    $savedAt = $article->updated_at?->locale('pl')->translatedFormat('j F Y, H:i');
@endphp

@section('content')
<style>
    /* Spójny fokus w całym formularzu (zamiast natywnych/obcych obrysów, np. czerwonego na <select>). */
    .blog-editor :is(input, select, textarea, button, a, summary, [tabindex]):focus-visible {
        outline: 2px solid var(--color-brand-dark);
        outline-offset: 2px;
    }
    .blog-editor :is(input, select, textarea):focus { border-color: var(--color-brand-dark); }
    /* Przeglądarki malują pola „nieprawidłowe" na czerwono już przy interakcji — błąd pokazuje tylko serwer (aria-invalid). */
    .blog-editor :is(input, select, textarea):user-invalid { box-shadow: none; }
    .blog-editor :is(input, select, textarea):-moz-ui-invalid { box-shadow: none; }
    .blog-editor :is(input, select, textarea)[aria-invalid="true"] { border-color: #b91c1c; border-width: 2px; }

    .blog-editor .be-tab[aria-selected="true"] { background: #fff; color: var(--color-ink); box-shadow: inset 0 -3px 0 var(--color-brand-dark); }
    .blog-editor .be-layout { display: grid; gap: 1.5rem; }
    @media (min-width: 1024px) {
        .blog-editor .be-layout { grid-template-columns: minmax(0, 1fr) 18rem; align-items: start; }
        .blog-editor .be-side { position: sticky; top: 9rem; }
    }
    .blog-editor .be-actions { position: sticky; top: 4rem; z-index: 15; }

    /* Edytor Markdown */
    .md-textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace; font-size: 0.95rem; line-height: 1.6; min-height: 24rem; resize: vertical; tab-size: 4; }
    .md-panes { display: grid; gap: 1rem; }
    .md-panes[data-view="write"] .md-preview { display: none; }
    .md-panes[data-view="preview"] .md-write { display: none; }
    @media (min-width: 1024px) {
        .md-panes[data-view="split"] { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .md-panes[data-view="split"] .md-preview-inner { max-height: 80vh; overflow-y: auto; }
    }
    .md-preview-inner { min-height: 24rem; }
    .md-tool { display: inline-flex; min-width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; gap: 0.25rem; border-radius: 0.25rem; padding: 0 0.5rem; font-size: 0.875rem; font-weight: 700; color: var(--color-ink); }
    .md-tool:hover { background: var(--color-brand-light); color: var(--color-brand-dark); }
    .md-view-btn[aria-pressed="true"] { background: var(--color-ink); color: #fff; }
    .md-dialog::backdrop { background: rgb(0 0 0 / 0.5); }
    .md-dialog { width: min(40rem, calc(100vw - 2rem)); border-radius: 0.5rem; padding: 0; }
    .md-pick[aria-pressed="true"] { outline: 3px solid var(--color-brand-dark); outline-offset: 2px; }
    .seo-preview-title { color: #1a0dab; }
    .seo-preview-url { color: #006621; }
</style>

<form method="POST" id="blog-article-form" novalidate
    action="{{ $article->exists ? route('admin.wiem-feer.update', $article) : route('admin.wiem-feer.store') }}"
    enctype="multipart/form-data" class="blog-editor"
    data-first-error-tab="{{ $firstErrorTab }}" data-article-key="{{ $article->exists ? $article->id : 'new' }}">
    @csrf
    @if ($article->exists) @method('PUT') @endif

    {{-- ==================== PASEK AKCJI ==================== --}}
    <div class="be-actions -mx-4 mb-4 flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-3 shadow-sm sm:-mx-6 sm:px-6">
        <p class="text-sm text-muted" id="be-status">
            @if ($article->exists)
                <span class="font-bold text-ink">{{ $article->statusLabel() }}</span>
                @if ($savedAt) · zapisano {{ $savedAt }} @endif
            @else
                <span class="font-bold text-ink">Nowy artykuł</span> · jeszcze niezapisany
            @endif
            <span data-dirty-note hidden> · <span class="font-bold text-ink">masz niezapisane zmiany</span></span>
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @if ($article->exists)
                <a href="{{ $article->previewUrl() }}" target="_blank" rel="noopener" class="{{ $btnSecondary }}">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd<span class="sr-only"> zapisanej wersji (otwiera się w nowej karcie)</span>
                </a>
            @endif
            <button type="submit" name="after_save" value="close" class="{{ $btnSecondary }}">Zapisz i zamknij</button>
            <button type="submit" name="after_save" value="edit" class="{{ $btnPrimary }}">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz
            </button>
        </div>
    </div>

    {{-- ==================== PODSUMOWANIE BŁĘDÓW ==================== --}}
    @if ($errorList->isNotEmpty())
        <div id="error-summary" class="mb-6 rounded-md bg-red-50 p-4 text-red-900" role="alert" tabindex="-1" aria-labelledby="error-summary-heading">
            <h2 id="error-summary-heading" class="text-base font-bold">
                Nie zapisano artykułu — popraw {{ $errorList->count() === 1 ? 'błąd' : 'błędy (' . $errorList->count() . ')' }}:
            </h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach ($errorList as $e)
                    <li>
                        <a href="#{{ $e['target'] }}" data-error-link data-tab="{{ $e['tab'] }}" data-target="{{ $e['target'] }}"
                            class="font-bold text-red-900 underline underline-offset-2 hover:no-underline">{{ $e['message'] }}</a>
                        <span class="text-red-900">(zakładka „{{ $tabs[$e['tab']]['label'] }}”)</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="be-layout">
        <div class="min-w-0">
            {{-- ==================== ZAKŁADKI ==================== --}}
            <div role="tablist" aria-label="Sekcje artykułu" class="flex flex-wrap gap-1 rounded-t-md bg-gray-100 p-1" data-tablist>
                @foreach ($tabs as $key => $tab)
                    @php $hasError = in_array($key, $tabsWithErrors, true); @endphp
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                        data-tab="{{ $key }}"
                        class="be-tab inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold text-ink hover:bg-white hover:text-brand-dark">
                        <i class="fa-solid {{ $tab['icon'] }} text-brand-dark" aria-hidden="true"></i>
                        {{ $tab['label'] }}
                        @if ($hasError)
                            <i class="fa-solid fa-circle-exclamation text-red-700" aria-hidden="true"></i><span class="sr-only"> (zawiera błędy)</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ===== Treść ===== --}}
            <div role="tabpanel" id="panel-tresc" aria-labelledby="tab-tresc" tabindex="0" data-panel="tresc"
                class="space-y-5 rounded-b-md bg-white p-6">
                <div>
                    <label for="title" class="mb-1 block text-sm font-bold">Tytuł <span class="font-normal text-muted">(wymagany)</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $article->title) }}" required aria-required="true" maxlength="255"
                        {{ $invalid('title') }} @error('title') aria-describedby="title-error" @enderror class="{{ $input }} text-lg">
                    @error('title') <p id="title-error" class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold">Adres URL (slug) <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <div class="flex flex-wrap items-center gap-1">
                        <span class="text-sm text-muted" aria-hidden="true">{{ $publicBase }}</span>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $article->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                            aria-describedby="slug-help @error('slug') slug-error @enderror" {{ $invalid('slug') }} class="{{ $input }} min-w-0 flex-1">
                    </div>
                    <p id="slug-help" class="mt-1 text-xs text-muted">Pełny adres: {{ $publicBase }}<span data-slug-preview>{{ old('slug', $article->slug) ?: '…' }}</span></p>
                    @error('slug') <p id="slug-error" class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="excerpt" class="mb-1 block text-sm font-bold">Zajawka <span class="font-normal text-muted">(opcjonalnie — pogrubiony wstęp i opis na liście)</span></label>
                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="500" {{ $invalid('excerpt') }} @error('excerpt') aria-describedby="excerpt-error" @enderror
                        class="{{ $input }}">{{ old('excerpt', $article->excerpt) }}</textarea>
                    @error('excerpt') <p id="excerpt-error" class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <input type="hidden" name="body_format" id="body_format" value="{{ $format }}">
                <p class="sr-only" role="status" aria-live="polite" data-format-status></p>

                {{-- Edytor wizualny (tylko wpisy zapisane jako HTML) --}}
                @if ($hasWysiwyg)
                    <div data-format-pane="html" @if ($format !== 'html') hidden @endif>
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <label for="editor-body" class="block text-sm font-bold">Treść <span class="font-normal text-muted">(edytor wizualny)</span></label>
                            <button type="button" data-switch-to-markdown class="{{ $btnSecondary }}">
                                <i class="fa-brands fa-markdown" aria-hidden="true"></i> Przełącz na Markdown
                            </button>
                        </div>
                        @include('admin.partials.editor', ['name' => 'body', 'value' => old('body', $article->body)])
                        @error('body') <p id="body-error" class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Edytor Markdown --}}
                <div data-format-pane="markdown" @if ($format !== 'markdown' && ! $htmlAfterSave) hidden @endif class="space-y-3" data-md-editor
                    data-preview-url="{{ route('admin.wiem-feer.podglad-markdown') }}"
                    data-images-url="{{ route('admin.multimedia.images') }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label for="md-body" id="md-body-label" class="block text-sm font-bold">Treść <span class="font-normal text-muted">(Markdown)</span></label>
                        <div class="flex flex-wrap items-center gap-2">
                            <div role="group" aria-label="Widok edytora" class="inline-flex gap-1 rounded bg-gray-100 p-1">
                                <button type="button" class="md-view-btn rounded px-3 py-1.5 text-sm font-bold text-ink" data-md-view="write" aria-pressed="true">Pisanie</button>
                                <button type="button" class="md-view-btn rounded px-3 py-1.5 text-sm font-bold text-ink" data-md-view="preview" aria-pressed="false">Podgląd</button>
                                <button type="button" class="md-view-btn hidden rounded px-3 py-1.5 text-sm font-bold text-ink lg:inline-block" data-md-view="split" aria-pressed="false">Obok siebie</button>
                            </div>
                            @if ($hasWysiwyg)
                                <button type="button" data-switch-to-html class="{{ $btnSecondary }}">Wróć do edytora wizualnego</button>
                            @else
                                <button type="button" data-switch-to-html data-no-wysiwyg @if ($htmlAfterSave) hidden @endif class="{{ $btnSecondary }}">Przełącz na edytor wizualny</button>
                            @endif
                        </div>
                    </div>

                    <div data-md-draft hidden class="flex flex-wrap items-center gap-3 rounded-md bg-brand-light p-3 text-sm text-ink" role="region" aria-label="Niezapisana kopia treści">
                        <p class="flex-1"><i class="fa-solid fa-clock-rotate-left mr-1 text-brand-dark" aria-hidden="true"></i>
                            W tej przeglądarce jest niezapisana kopia treści z <strong data-md-draft-time></strong>. Przywrócić ją?</p>
                        <button type="button" data-md-draft-restore class="{{ $btnPrimary }}">Przywróć kopię</button>
                        <button type="button" data-md-draft-discard class="{{ $btnSecondary }}">Odrzuć</button>
                    </div>

                    <div role="toolbar" aria-label="Formatowanie Markdown" aria-controls="md-body" data-md-toolbar
                        class="flex flex-wrap items-center gap-1 rounded bg-gray-50 p-1">
                        @foreach ([
                            ['bold', 'fa-bold', 'Pogrubienie', 'Ctrl+B', 'Control+B'],
                            ['italic', 'fa-italic', 'Kursywa', 'Ctrl+I', 'Control+I'],
                            ['h2', null, 'Nagłówek poziomu 2', null, null, 'H2'],
                            ['h3', null, 'Nagłówek poziomu 3', null, null, 'H3'],
                            ['ul', 'fa-list-ul', 'Lista punktowana', null, null],
                            ['ol', 'fa-list-ol', 'Lista numerowana', null, null],
                            ['quote', 'fa-quote-right', 'Cytat', null, null],
                            ['link', 'fa-link', 'Link', 'Ctrl+K', 'Control+K'],
                            ['image', 'fa-image', 'Obraz z biblioteki', null, null],
                            ['table', 'fa-table', 'Tabela', null, null],
                            ['code', 'fa-code', 'Kod', null, null],
                            ['hr', 'fa-minus', 'Linia pozioma', null, null],
                        ] as $i => $tool)
                            @php $tip = $tool[2] . ($tool[3] ? ' (' . $tool[3] . ')' : ''); @endphp
                            <button type="button" class="md-tool" data-md-action="{{ $tool[0] }}" tabindex="{{ $i === 0 ? '0' : '-1' }}"
                                aria-label="{{ $tip }}" title="{{ $tip }}" @if ($tool[4]) aria-keyshortcuts="{{ $tool[4] }}" @endif>
                                @if ($tool[1])<i class="fa-solid {{ $tool[1] }}" aria-hidden="true"></i>@else<span aria-hidden="true">{{ $tool[5] }}</span>@endif
                            </button>
                        @endforeach
                    </div>

                    <div class="md-panes" data-view="write" data-md-panes>
                        <div class="md-write">
                            <textarea id="md-body" name="body_markdown" rows="18" spellcheck="true" lang="pl"
                                aria-describedby="md-help md-stats @error('body_markdown') body_markdown-error @enderror @error('body') body-error @enderror"
                                {{ $errors->has('body_markdown') || ($errors->has('body') && ! $hasWysiwyg) ? 'aria-invalid=true' : '' }}
                                class="md-textarea {{ $input }}">{{ $markdownValue }}</textarea>
                        </div>
                        <section class="md-preview" aria-label="Podgląd treści">
                            <div class="md-preview-inner rounded border border-gray-300 bg-white p-5" data-md-preview tabindex="0" aria-label="Podgląd treści artykułu">
                                <div class="prose prose-lg max-w-none text-ink" data-md-preview-body>
                                    <p class="text-muted">Podgląd pojawi się tutaj.</p>
                                </div>
                            </div>
                        </section>
                    </div>
                    <p id="md-stats" class="text-sm text-muted"><span data-md-words>0</span> słów · ok. <span data-md-minutes>1</span> min czytania</p>
                    <p id="md-help" class="text-xs text-muted">Formatowanie w Markdown — przyciski powyżej albo skróty Ctrl+B, Ctrl+I, Ctrl+K. Kopia robocza zapisuje się w tej przeglądarce.</p>
                    @error('body_markdown') <p id="body_markdown-error" class="{{ $err }}">{{ $message }}</p> @enderror
                    @if (! $hasWysiwyg)
                        @error('body') <p id="body-error" class="{{ $err }}">{{ $message }}</p> @enderror
                    @endif
                    <p data-no-wysiwyg-note @unless ($htmlAfterSave) hidden @endunless class="rounded-md bg-brand-light p-3 text-sm text-ink">
                        Po zapisaniu treść zostanie zamieniona na HTML i otworzy się w edytorze wizualnym. Źródło Markdown zostanie zachowane.
                    </p>

                    <details class="rounded-md bg-gray-50 p-4 text-sm">
                        <summary class="cursor-pointer font-bold text-ink">Ściągawka Markdown</summary>
                        <table class="mt-3 w-full text-left">
                            <caption class="sr-only">Najczęstsze znaczniki Markdown</caption>
                            <thead><tr><th scope="col" class="py-1 pr-4">Wpisz</th><th scope="col" class="py-1">Otrzymasz</th></tr></thead>
                            <tbody class="align-top">
                                <tr><td class="py-1 pr-4 font-mono">## Nagłówek</td><td>nagłówek sekcji (H2; ### to H3)</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">**tekst**</td><td><strong>pogrubienie</strong></td></tr>
                                <tr><td class="py-1 pr-4 font-mono">_tekst_</td><td><em>kursywa</em></td></tr>
                                <tr><td class="py-1 pr-4 font-mono">- element</td><td>lista punktowana</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">1. element</td><td>lista numerowana</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">[opis](https://…)</td><td>link — opis mówi, dokąd prowadzi</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">![tekst alternatywny](adres)</td><td>obraz z opisem dla czytników ekranu</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">&gt; cytat</td><td>cytat</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">`kod`</td><td>kod w tekście; blok kodu między liniami ```</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">| A | B |<br>| --- | --- |</td><td>tabela (pierwszy wiersz to nagłówki)</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">---</td><td>linia pozioma</td></tr>
                                <tr><td class="py-1 pr-4 font-mono">pusta linia</td><td>nowy akapit</td></tr>
                            </tbody>
                        </table>
                        <p class="mt-3 text-muted">Nagłówki zaczynaj od poziomu 2 (tytuł artykułu to poziom 1) i nie przeskakuj poziomów. Można też wpisać zwykły HTML.</p>
                    </details>
                </div>
            </div>

            {{-- ===== Autorzy i kategorie ===== --}}
            @php $selectedCo = array_map('intval', (array) old('co_authors', $coAuthorIds)); @endphp
            <div role="tabpanel" id="panel-autorzy" aria-labelledby="tab-autorzy" tabindex="0" data-panel="autorzy" hidden
                class="space-y-5 rounded-b-md bg-white p-6">
                <fieldset class="space-y-4 rounded-lg bg-gray-50 p-4">
                    <legend class="px-1 text-sm font-bold">Autorzy</legend>
                    <div>
                        <label for="author_id" class="mb-1 block text-sm font-bold">Autor główny</label>
                        <select id="author_id" name="author_id" aria-describedby="author_id-help @error('author_id') author_id-error @enderror" {{ $invalid('author_id') }} class="{{ $input }}">
                            <option value="">— bez profilu autora —</option>
                            @foreach ($authors as $a)
                                <option value="{{ $a->id }}" @selected((int) old('author_id', $article->author_id) === $a->id)>{{ $a->name }}{{ $a->is_active ? '' : ' (nieaktywny)' }}</option>
                            @endforeach
                        </select>
                        <p id="author_id-help" class="mt-1 text-xs text-muted">Profile autorów dodasz w <a href="{{ route('admin.autorzy-bloga.index') }}" class="{{ $link }}">Autorzy</a>. Nowy wpis ma domyślnie autora powiązanego z Twoim kontem.</p>
                        @error('author_id') <p id="author_id-error" class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    @if ($authors->count() > 1)
                        <div role="group" aria-labelledby="co-authors-label">
                            <p id="co-authors-label" class="mb-1 text-sm font-bold">Współautorzy <span class="font-normal text-muted">(opcjonalnie)</span></p>
                            <div class="grid gap-1 sm:grid-cols-2">
                                @foreach ($authors as $a)
                                    <label class="flex min-h-9 items-center gap-2 text-sm">
                                        <input type="checkbox" name="co_authors[]" value="{{ $a->id }}" @checked(in_array($a->id, $selectedCo, true))
                                            @if ($loop->first) id="co-authors-first" @endif
                                            class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark"> {{ $a->name }}
                                    </label>
                                @endforeach
                            </div>
                            @error('co_authors') <p class="{{ $err }}">{{ $message }}</p> @enderror
                            @error('co_authors.*') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="author_name" class="mb-1 block text-sm font-bold">Podpis tekstowy <span class="font-normal text-muted">(gdy brak profilu)</span></label>
                            <input type="text" id="author_name" name="author_name" value="{{ old('author_name', $article->author_name) }}"
                                placeholder="np. Zespół FEER" {{ $invalid('author_name') }} class="{{ $input }}">
                            @error('author_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="author_bio" class="mb-1 block text-sm font-bold">Nota o autorze <span class="font-normal text-muted">(gdy brak profilu)</span></label>
                            <textarea id="author_bio" name="author_bio" rows="2" maxlength="600" {{ $invalid('author_bio') }}
                                class="{{ $input }}">{{ old('author_bio', $article->author_bio) }}</textarea>
                            @error('author_bio') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="mb-1 block text-sm font-bold">Kategoria</label>
                        <select id="category_id" name="category_id" {{ $invalid('category_id') }} class="{{ $input }}">
                            <option value="">— bez kategorii —</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected((int) old('category_id', $article->category_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted"><a href="{{ route('admin.kategorie-bloga.index') }}" class="{{ $link }}">Zarządzaj kategoriami</a></p>
                        @error('category_id') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tags" class="mb-1 block text-sm font-bold">Tagi <span class="font-normal text-muted">(po przecinku)</span></label>
                        <input type="text" id="tags" name="tags" value="{{ old('tags', $tagsValue) }}" placeholder="np. dostępność, WCAG, edukacja"
                            aria-describedby="tags-help" {{ $invalid('tags') }} class="{{ $input }}">
                        <p id="tags-help" class="mt-1 text-xs text-muted">
                            Nowe tagi utworzą się same.
                            @if ($tagsAll->isNotEmpty()) Istniejące: {{ $tagsAll->take(30)->join(', ') }}@endif
                        </p>
                        @error('tags') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ===== Zdjęcie ===== --}}
            <div role="tabpanel" id="panel-zdjecie" aria-labelledby="tab-zdjecie" tabindex="0" data-panel="zdjecie" hidden
                class="space-y-5 rounded-b-md bg-white p-6">
                @include('blog::admin.partials.image-field', [
                    'prefix' => 'image', 'legend' => 'Zdjęcie wyróżniające (opcjonalnie — na liście, we wpisie i przy udostępnianiu)',
                    'fileName' => 'image', 'currentUrl' => $article->imageUrl(), 'currentAlt' => $article->image_alt,
                    'removeName' => 'remove_image', 'altName' => 'image_alt', 'altValue' => old('image_alt', $article->image_alt),
                    'altLabel' => 'Tekst alternatywny zdjęcia',
                    'altHelp' => 'Opisz, co przedstawia zdjęcie — czytają to osoby korzystające z czytników ekranu. Wymagany, chyba że zdjęcie jest dekoracyjne.',
                    'decorativeName' => 'image_decorative', 'decorativeValue' => (bool) old('image_decorative', $article->image_decorative),
                ])
            </div>

            {{-- ===== Publikacja ===== --}}
            @php $currentWip = old('wip_mode', $article->wip_mode); @endphp
            <div role="tabpanel" id="panel-publikacja" aria-labelledby="tab-publikacja" tabindex="0" data-panel="publikacja" hidden
                class="space-y-6 rounded-b-md bg-white p-6">
                <div class="space-y-4">
                    <label class="flex items-start gap-2">
                        <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $article->is_published ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-400 text-brand-dark focus:ring-brand-dark" data-published-toggle>
                        <span>
                            <span class="block text-sm font-bold">Opublikowany</span>
                            <span class="block text-xs text-muted">Bez zaznaczenia artykuł jest szkicem — widać go tylko w panelu i przez link podglądu.</span>
                        </span>
                    </label>

                    <div>
                        <label for="published_at" class="mb-1 block text-sm font-bold">Data publikacji <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="published_at" name="published_at"
                            value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}"
                            aria-describedby="published_at-help" {{ $invalid('published_at') }} class="{{ $input }} max-w-xs">
                        <p id="published_at-help" class="mt-1 text-xs text-muted">Pusto = data zapisania (przy publikacji). Data w przyszłości ukrywa artykuł do tego momentu.</p>
                        @error('published_at') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $article->is_featured ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold">Wyróżniony wpis</span>
                            <span class="block text-xs text-muted">Pokazywany na górze listy Wiem FEER w dużej karcie (gdy wyróżnionych jest kilka — najnowszy).</span>
                        </span>
                    </label>
                </div>

                <div class="space-y-5 rounded-lg bg-gray-50 p-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Dostępność artykułu</h2>
                        <p class="mt-1 text-xs text-muted">Tymczasowo wyłącz artykuł lub oznacz, że jest w przygotowaniu. Działa niezależnie od statusu publikacji.</p>
                    </div>

                    <div>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="is_disabled" value="1" {{ old('is_disabled', $article->is_disabled ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-400 text-brand-dark focus:ring-brand-dark" data-disable-toggle>
                            <span>
                                <span class="block text-sm font-bold">Wyłącz artykuł</span>
                                <span class="block text-xs text-muted">Odwiedzający zamiast treści zobaczą pełnoekranowy komunikat, że artykuł jest tymczasowo niedostępny.</span>
                            </span>
                        </label>
                        <div class="mt-3 sm:pl-6" data-disable-message>
                            <label for="disabled_message" class="mb-1 block text-sm font-bold">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="disabled_message" name="disabled_message" rows="2" placeholder="{{ \Modules\Blog\Models\BlogArticle::DEFAULT_DISABLED_MESSAGE }}"
                                {{ $invalid('disabled_message') }} class="{{ $input }}">{{ old('disabled_message', $article->disabled_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu.</p>
                            @error('disabled_message') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-bold">Artykuł w przygotowaniu</legend>
                        <p class="mb-3 text-xs text-muted">Oznacz, że trwają prace nad artykułem — wybierz, jak poinformować odwiedzających.</p>
                        <div class="space-y-2" data-wip-modes>
                            <label class="flex items-start gap-2">
                                <input type="radio" id="wip_mode-none" name="wip_mode" value="" {{ ! $currentWip ? 'checked' : '' }}
                                    class="mt-0.5 border-gray-400 text-brand-dark focus:ring-brand-dark">
                                <span class="text-sm">Wyłączone <span class="text-muted">— artykuł działa normalnie</span></span>
                            </label>
                            @foreach (\Modules\Blog\Models\BlogArticle::WIP_MODES as $value => $label)
                                <label class="flex items-start gap-2">
                                    <input type="radio" name="wip_mode" value="{{ $value }}" {{ $currentWip === $value ? 'checked' : '' }}
                                        class="mt-0.5 border-gray-400 text-brand-dark focus:ring-brand-dark">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('wip_mode') <p class="{{ $err }}">{{ $message }}</p> @enderror

                        <div class="mt-3 sm:pl-6 {{ $currentWip ? '' : 'hidden' }}" data-wip-message>
                            <label for="wip_message" class="mb-1 block text-sm font-bold">Treść komunikatu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="wip_message" name="wip_message" rows="2" placeholder="{{ \Modules\Blog\Models\BlogArticle::DEFAULT_WIP_NOTICE_MESSAGE }}"
                                {{ $invalid('wip_message') }} class="{{ $input }}">{{ old('wip_message', $article->wip_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu dla wybranego trybu.</p>
                            @error('wip_message') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    </fieldset>
                </div>
            </div>

            {{-- ===== SEO ===== --}}
            <div role="tabpanel" id="panel-seo" aria-labelledby="tab-seo" tabindex="0" data-panel="seo" hidden
                class="space-y-5 rounded-b-md bg-white p-6" data-seo data-public-base="{{ $publicBase }}">
                <div>
                    <label for="meta_title" class="mb-1 block text-sm font-bold">Tytuł w wyszukiwarkach <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="meta_title" name="meta_title" maxlength="255" value="{{ old('meta_title', $article->meta_title) }}"
                        placeholder="pusto = tytuł artykułu" aria-describedby="meta_title-count" {{ $invalid('meta_title') }} class="{{ $input }}">
                    <p id="meta_title-count" class="mt-1 text-xs text-muted" data-count-for="meta_title" data-count-max="60">Zalecane do 60 znaków.</p>
                    @error('meta_title') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="meta_description" class="mb-1 block text-sm font-bold">Opis w wyszukiwarkach <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="pusto = zajawka"
                        aria-describedby="meta_description-count" {{ $invalid('meta_description') }} class="{{ $input }}">{{ old('meta_description', $article->meta_description) }}</textarea>
                    <p id="meta_description-count" class="mt-1 text-xs text-muted" data-count-for="meta_description" data-count-min="120" data-count-max="160">Najlepiej 120–160 znaków.</p>
                    @error('meta_description') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <section aria-labelledby="seo-preview-heading" class="rounded-md bg-gray-50 p-4">
                    <h2 id="seo-preview-heading" class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Tak może wyglądać wynik w Google</h2>
                    <div class="max-w-xl rounded bg-white p-4">
                        <p class="seo-preview-url truncate text-sm" data-seo-url>{{ $publicBase }}{{ $article->slug ?: '…' }}</p>
                        <p class="seo-preview-title mt-1 text-xl leading-snug" data-seo-title>{{ $article->exists ? $article->metaTitle() : 'Tytuł artykułu' }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-ink" data-seo-desc>{{ $article->exists ? \Illuminate\Support\Str::limit($article->metaDescription(), 160) : 'Opis artykułu…' }}</p>
                    </div>
                </section>
            </div>
        </div>

        {{-- ==================== KOLUMNA BOCZNA: PUBLIKACJA ==================== --}}
        <aside class="be-side space-y-4" aria-label="Podsumowanie publikacji">
            <div class="rounded-md bg-white p-5">
                <h2 class="text-base font-bold text-ink">Publikacja</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-muted">Status</dt><dd class="font-bold text-ink" data-side-status>{{ $article->exists ? $article->statusLabel() : 'Nowy (szkic)' }}</dd></div>
                    @if ($article->exists && $article->publishedDate())
                        <div class="flex justify-between gap-3"><dt class="text-muted">Data</dt><dd class="text-ink">{{ $article->publishedDate()->format('d.m.Y H:i') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-muted">Edytor treści</dt><dd class="text-ink" data-side-format>{{ $format === 'markdown' ? 'Markdown' : 'Wizualny (HTML)' }}</dd></div>
                    @if ($savedAt)
                        <div class="flex justify-between gap-3"><dt class="text-muted">Zapisano</dt><dd class="text-right text-ink">{{ $savedAt }}</dd></div>
                    @endif
                </dl>
                <button type="button" data-goto-tab="publikacja" class="mt-4 text-sm {{ $link }}">Zmień ustawienia publikacji</button>
            </div>
            <div class="rounded-md bg-white p-5 text-sm">
                @if ($article->exists && $article->isVisible())
                    <p><a href="{{ route('blog.show', $article) }}" target="_blank" rel="noopener" class="{{ $link }}">Zobacz na stronie<span class="sr-only"> (otwiera się w nowej karcie)</span></a></p>
                @endif
                <p class="mt-2"><a href="{{ route('admin.wiem-feer.index') }}" class="{{ $link }}">Wróć do listy artykułów</a></p>
            </div>
        </aside>
    </div>

    {{-- ==================== OKNA: LINK I OBRAZ ==================== --}}
    <dialog class="md-dialog" id="md-link-dialog" aria-labelledby="md-link-title">
        <div class="space-y-4 p-6">
            <h2 id="md-link-title" class="text-lg font-bold text-ink">Wstaw link</h2>
            <div>
                <label for="md-link-url" class="mb-1 block text-sm font-bold">Adres (URL)</label>
                <input type="text" id="md-link-url" inputmode="url" placeholder="https://… albo /strona" aria-describedby="md-link-error" class="{{ $input }}">
            </div>
            <div>
                <label for="md-link-text" class="mb-1 block text-sm font-bold">Tekst linku</label>
                <input type="text" id="md-link-text" aria-describedby="md-link-text-help" class="{{ $input }}">
                <p id="md-link-text-help" class="mt-1 text-xs text-muted">Napisz, dokąd prowadzi link — unikaj „kliknij tutaj”.</p>
            </div>
            <p id="md-link-error" class="text-sm font-bold text-red-700" role="alert" data-dialog-error></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-dialog-cancel class="{{ $btnSecondary }}">Anuluj</button>
                <button type="button" data-dialog-ok class="{{ $btnPrimary }}">Wstaw link</button>
            </div>
        </div>
    </dialog>

    <dialog class="md-dialog" id="md-image-dialog" aria-labelledby="md-image-title">
        <div class="space-y-4 p-6">
            <h2 id="md-image-title" class="text-lg font-bold text-ink">Wstaw obraz z biblioteki</h2>
            <p class="text-sm text-muted" role="status" data-image-status>Wczytuję zdjęcia…</p>
            <div class="grid max-h-64 grid-cols-3 gap-2 overflow-y-auto p-1 sm:grid-cols-4" role="group" aria-label="Zdjęcia z biblioteki" data-image-grid></div>
            <div>
                <label for="md-image-url" class="mb-1 block text-sm font-bold">Adres obrazu</label>
                <input type="text" id="md-image-url" placeholder="wybierz zdjęcie powyżej albo wklej adres" class="{{ $input }}">
            </div>
            <div>
                <label for="md-image-alt" class="mb-1 block text-sm font-bold">Tekst alternatywny <span class="font-normal text-muted">(wymagany)</span></label>
                <input type="text" id="md-image-alt" required aria-required="true" aria-describedby="md-image-alt-help md-image-error" class="{{ $input }}">
                <p id="md-image-alt-help" class="mt-1 text-xs text-muted">Opisz, co przedstawia obraz — usłyszą to osoby korzystające z czytników ekranu.</p>
            </div>
            <p id="md-image-error" class="text-sm font-bold text-red-700" role="alert" data-dialog-error></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-dialog-cancel class="{{ $btnSecondary }}">Anuluj</button>
                <button type="button" data-dialog-ok class="{{ $btnPrimary }}">Wstaw obraz</button>
            </div>
        </div>
    </dialog>
</form>

<script>
(function () {
    'use strict';
    var form = document.getElementById('blog-article-form');
    if (!form) return;
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || (form.querySelector('input[name=_token]') || {}).value || '';
    var storage = {
        get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* brak dostępu do pamięci */ } },
        del: function (k) { try { window.localStorage.removeItem(k); } catch (e) { /* j.w. */ } },
    };
    function confirmBox(message) {
        if (window.Alpine && Alpine.store && Alpine.store('confirm')) return Alpine.store('confirm').ask(message);
        return Promise.resolve(window.confirm(message));
    }

    // ── Zakładki (WAI-ARIA tabs: strzałki, Home/End, aktywna w kolejności Tab) ──
    var TAB_KEY = 'feer-blog-editor-tab';
    var tabs = Array.prototype.slice.call(form.querySelectorAll('[role=tab]'));
    function activate(name, focusTab) {
        var found = false;
        tabs.forEach(function (t) {
            var on = t.dataset.tab === name;
            if (on) found = true;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            var panel = document.getElementById(t.getAttribute('aria-controls'));
            if (panel) panel.hidden = !on;
            if (on && focusTab) t.focus();
        });
        if (found) storage.set(TAB_KEY, name);
        return found;
    }
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { activate(t.dataset.tab, false); });
        t.addEventListener('keydown', function (e) {
            var next = null;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = tabs[(i + 1) % tabs.length];
            else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = tabs[(i - 1 + tabs.length) % tabs.length];
            else if (e.key === 'Home') next = tabs[0];
            else if (e.key === 'End') next = tabs[tabs.length - 1];
            if (next) { e.preventDefault(); activate(next.dataset.tab, true); }
        });
    });
    form.querySelectorAll('[data-goto-tab]').forEach(function (b) {
        b.addEventListener('click', function () { activate(b.dataset.gotoTab, true); });
    });

    function focusField(id) {
        if (id === 'editor-body' && window.tinymce && tinymce.get('editor-body')) { tinymce.get('editor-body').focus(); return; }
        var el = document.getElementById(id);
        if (!el) return;
        el.scrollIntoView({ block: 'center' });
        el.focus({ preventScroll: true });
    }

    var firstErrorTab = form.dataset.firstErrorTab;
    var summary = document.getElementById('error-summary');
    if (firstErrorTab) {
        activate(firstErrorTab, false);
        if (summary) summary.focus();
    } else {
        var remembered = storage.get(TAB_KEY);
        if (remembered) activate(remembered, false);
    }
    form.querySelectorAll('[data-error-link]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            activate(a.dataset.tab, false);
            focusField(a.dataset.target);
        });
    });

    // ── Niezapisane zmiany ──
    var dirtyNote = form.querySelector('[data-dirty-note]');
    form.addEventListener('input', function () { if (dirtyNote) dirtyNote.hidden = false; });
    form.addEventListener('change', function () { if (dirtyNote) dirtyNote.hidden = false; });

    // ── Slug, SEO: liczniki i podgląd wyniku wyszukiwania ──
    var title = document.getElementById('title');
    var slug = document.getElementById('slug');
    var excerpt = document.getElementById('excerpt');
    var metaTitle = document.getElementById('meta_title');
    var metaDesc = document.getElementById('meta_description');
    var slugPreview = form.querySelector('[data-slug-preview]');
    var seo = form.querySelector('[data-seo]');
    function slugify(s) {
        var map = { 'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z' };
        return (s || '').toLowerCase().replace(/[ąćęłńóśźż]/g, function (c) { return map[c]; })
            .normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    function updateSeo() {
        var s = (slug.value.trim() ? slugify(slug.value) : slugify(title.value)) || '…';
        if (slugPreview) slugPreview.textContent = s;
        if (!seo) return;
        seo.querySelector('[data-seo-url]').textContent = seo.dataset.publicBase + s;
        seo.querySelector('[data-seo-title]').textContent = metaTitle.value.trim() || title.value.trim() || 'Tytuł artykułu';
        var d = metaDesc.value.trim() || excerpt.value.trim() || 'Opis artykułu…';
        seo.querySelector('[data-seo-desc]').textContent = d.length > 160 ? d.slice(0, 157) + '…' : d;
        seo.querySelectorAll('[data-count-for]').forEach(function (p) {
            var len = document.getElementById(p.dataset.countFor).value.trim().length;
            var max = +p.dataset.countMax, min = +(p.dataset.countMin || 0);
            var hint = min ? ' (zalecane ' + min + '–' + max + ')' : ' (zalecane do ' + max + ')';
            var verdict = len === 0 ? ' — użyjemy wartości domyślnej.' : (len > max ? ' — za długi, Google może go uciąć.' : (min && len < min ? ' — mógłby być dłuższy.' : ' — w sam raz.'));
            p.textContent = 'Znaków: ' + len + hint + verdict;
        });
    }
    [title, slug, excerpt, metaTitle, metaDesc].forEach(function (el) { if (el) el.addEventListener('input', updateSeo); });
    updateSeo();

    // ── Dostępność artykułu: pola komunikatów tylko, gdy opcja aktywna ──
    var disableToggle = form.querySelector('[data-disable-toggle]');
    var disableMessage = form.querySelector('[data-disable-message]');
    if (disableToggle && disableMessage) {
        var syncDisable = function () { disableMessage.classList.toggle('hidden', !disableToggle.checked); };
        disableToggle.addEventListener('change', syncDisable);
        syncDisable();
    }
    var wipMessage = form.querySelector('[data-wip-message]');
    var wipRadios = form.querySelectorAll('[data-wip-modes] input[name="wip_mode"]');
    if (wipMessage && wipRadios.length) {
        var syncWip = function () {
            var selected = form.querySelector('[data-wip-modes] input[name="wip_mode"]:checked');
            wipMessage.classList.toggle('hidden', !selected || selected.value === '');
        };
        wipRadios.forEach(function (r) { r.addEventListener('change', syncWip); });
        syncWip();
    }

    // ── Edytor Markdown ──
    var md = form.querySelector('[data-md-editor]');
    var ta = document.getElementById('md-body');
    var formatInput = document.getElementById('body_format');
    var formatStatus = form.querySelector('[data-format-status]');
    var sideFormat = form.querySelector('[data-side-format]');
    var DRAFT_KEY = 'feer-blog-md-draft:' + form.dataset.articleKey;

    function autoGrow() {
        if (!ta || ta.offsetParent === null) return;
        ta.style.height = 'auto';
        ta.style.height = Math.max(ta.scrollHeight + 2, 384) + 'px';
    }

    function countWords(text) {
        var plain = text.replace(/```[\s\S]*?```/g, ' ').replace(/<[^>]+>/g, ' ').replace(/!\[([^\]]*)\]\([^)]*\)/g, ' ')
            .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1').replace(/[#>*_`~|\-]+/g, ' ').trim();
        return plain ? plain.split(/\s+/).length : 0;
    }
    function updateStats(words) {
        if (!md) return;
        var w = typeof words === 'number' ? words : countWords(ta.value);
        md.querySelector('[data-md-words]').textContent = w;
        md.querySelector('[data-md-minutes]').textContent = Math.max(1, Math.ceil(w / 200));
    }

    // Wstawianie z zachowaniem cofania (Ctrl+Z): execCommand, a gdy niedostępny — setRangeText.
    function replaceRange(start, end, text, selStart, selEnd) {
        ta.focus();
        ta.setSelectionRange(start, end);
        var ok = false;
        try { ok = document.execCommand('insertText', false, text); } catch (e) { ok = false; }
        if (!ok || ta.value.substring(start, start + text.length) !== text) {
            ta.setRangeText(text, start, end, 'end');
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }
        ta.setSelectionRange(selStart == null ? start + text.length : selStart, selEnd == null ? start + text.length : selEnd);
    }
    function wrap(before, after, placeholder) {
        var s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.slice(s, e) || placeholder;
        replaceRange(s, e, before + sel + after, s + before.length, s + before.length + sel.length);
    }
    function prefixLines(fn) {
        var v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
        var ls = v.lastIndexOf('\n', s - 1) + 1;
        var le = v.indexOf('\n', e); if (le === -1) le = v.length;
        var lines = v.slice(ls, le).split('\n');
        var out = lines.map(function (l, i) { return fn(l, i); }).join('\n');
        replaceRange(ls, le, out, ls, ls + out.length);
    }
    function insertBlock(text) {
        var v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
        var before = v.slice(0, s), pre = before === '' ? '' : (/\n\n$/.test(before) ? '' : (/\n$/.test(before) ? '\n' : '\n\n'));
        var post = /^\n/.test(v.slice(e)) ? '\n' : '\n\n';
        replaceRange(s, e, pre + text + post);
    }

    var actions = {
        bold: function () { wrap('**', '**', 'pogrubiony tekst'); },
        italic: function () { wrap('_', '_', 'kursywa'); },
        h2: function () { prefixLines(function (l) { return '## ' + l.replace(/^#{1,6}\s*/, ''); }); },
        h3: function () { prefixLines(function (l) { return '### ' + l.replace(/^#{1,6}\s*/, ''); }); },
        ul: function () { prefixLines(function (l) { return '- ' + l.replace(/^(\s*[-*+]|\s*\d+\.)\s+/, ''); }); },
        ol: function () { prefixLines(function (l, i) { return (i + 1) + '. ' + l.replace(/^(\s*[-*+]|\s*\d+\.)\s+/, ''); }); },
        quote: function () { prefixLines(function (l) { return '> ' + l; }); },
        code: function () {
            var sel = ta.value.slice(ta.selectionStart, ta.selectionEnd);
            if (sel.indexOf('\n') !== -1) insertBlock('```\n' + sel + '\n```'); else wrap('`', '`', 'kod');
        },
        table: function () { insertBlock('| Nagłówek 1 | Nagłówek 2 |\n| --- | --- |\n| Komórka | Komórka |\n| Komórka | Komórka |'); },
        hr: function () { insertBlock('---'); },
        link: function () { openLinkDialog(); },
        image: function () { openImageDialog(); },
    };

    // Okna dialogowe: natywny <dialog> (fokus w środku, Esc zamyka), powrót fokusu do edytora.
    function setupDialog(dialog, onOk) {
        var error = dialog.querySelector('[data-dialog-error]');
        dialog.querySelector('[data-dialog-cancel]').addEventListener('click', function () { dialog.close(); });
        dialog.querySelector('[data-dialog-ok]').addEventListener('click', function () {
            var msg = onOk();
            if (msg) { error.textContent = msg; return; }
            dialog.close();
        });
        dialog.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); dialog.querySelector('[data-dialog-ok]').click(); }
        });
        dialog.addEventListener('close', function () { error.textContent = ''; ta.focus(); });
    }
    var savedSel = [0, 0];
    var linkDialog = document.getElementById('md-link-dialog');
    function openLinkDialog() {
        savedSel = [ta.selectionStart, ta.selectionEnd];
        var sel = ta.value.slice(savedSel[0], savedSel[1]);
        document.getElementById('md-link-url').value = /^(https?:\/\/|\/)/.test(sel) ? sel : '';
        document.getElementById('md-link-text').value = /^(https?:\/\/|\/)/.test(sel) ? '' : sel;
        linkDialog.showModal();
        document.getElementById(document.getElementById('md-link-url').value ? 'md-link-text' : 'md-link-url').focus();
    }
    setupDialog(linkDialog, function () {
        var url = document.getElementById('md-link-url').value.trim();
        var text = document.getElementById('md-link-text').value.trim() || url;
        if (!url) return 'Podaj adres linku.';
        if (/^\s*(javascript|vbscript|data):/i.test(url)) return 'Ten rodzaj adresu jest niedozwolony.';
        if (/[\s()]/.test(url)) url = '<' + url + '>';
        replaceRange(savedSel[0], savedSel[1], '[' + text.replace(/([\[\]])/g, '\\$1') + '](' + url + ')');
        return '';
    });

    var imageDialog = document.getElementById('md-image-dialog');
    var imagesLoaded = false;
    function openImageDialog() {
        savedSel = [ta.selectionStart, ta.selectionEnd];
        document.getElementById('md-image-url').value = '';
        document.getElementById('md-image-alt').value = ta.value.slice(savedSel[0], savedSel[1]);
        imageDialog.showModal();
        if (!imagesLoaded) loadImages();
    }
    function loadImages() {
        var status = imageDialog.querySelector('[data-image-status]');
        var grid = imageDialog.querySelector('[data-image-grid]');
        fetch(md.dataset.imagesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (list) {
                imagesLoaded = true;
                status.textContent = list.length ? 'Wybierz zdjęcie (' + list.length + ') albo wklej adres.' : 'Biblioteka nie zawiera jeszcze zdjęć — wklej adres obrazu.';
                list.forEach(function (m) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'md-pick overflow-hidden rounded';
                    b.setAttribute('aria-pressed', 'false');
                    var img = document.createElement('img');
                    img.src = m.url; img.loading = 'lazy'; img.alt = m.alt || m.file_name;
                    img.className = 'aspect-[4/3] w-full object-cover';
                    b.appendChild(img);
                    b.addEventListener('click', function () {
                        grid.querySelectorAll('[aria-pressed]').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
                        b.setAttribute('aria-pressed', 'true');
                        document.getElementById('md-image-url').value = m.url;
                        var alt = document.getElementById('md-image-alt');
                        if (!alt.value && m.alt) alt.value = m.alt;
                    });
                    grid.appendChild(b);
                });
            })
            .catch(function () { status.textContent = 'Nie udało się wczytać biblioteki zdjęć — wklej adres obrazu.'; });
    }
    setupDialog(imageDialog, function () {
        var url = document.getElementById('md-image-url').value.trim();
        var alt = document.getElementById('md-image-alt').value.trim();
        if (!url) return 'Wybierz zdjęcie albo podaj jego adres.';
        if (!alt) { document.getElementById('md-image-alt').focus(); return 'Wpisz tekst alternatywny — opisz, co przedstawia obraz.'; }
        if (/[\s()]/.test(url)) url = '<' + url + '>';
        replaceRange(savedSel[0], savedSel[1], '![' + alt.replace(/([\[\]])/g, '\\$1') + '](' + url + ')');
        return '';
    });

    if (md && ta) {
        // Pasek narzędzi: jeden przycisk w kolejności Tab, strzałki między przyciskami.
        var toolbar = md.querySelector('[data-md-toolbar]');
        var tools = Array.prototype.slice.call(toolbar.querySelectorAll('[data-md-action]'));
        tools.forEach(function (b, i) {
            b.addEventListener('mousedown', function (e) { e.preventDefault(); }); // zachowaj zaznaczenie w polu
            b.addEventListener('click', function () {
                tools.forEach(function (x) { x.tabIndex = -1; }); b.tabIndex = 0;
                actions[b.dataset.mdAction]();
            });
            b.addEventListener('keydown', function (e) {
                var n = null;
                if (e.key === 'ArrowRight') n = tools[(i + 1) % tools.length];
                else if (e.key === 'ArrowLeft') n = tools[(i - 1 + tools.length) % tools.length];
                else if (e.key === 'Home') n = tools[0];
                else if (e.key === 'End') n = tools[tools.length - 1];
                if (n) { e.preventDefault(); tools.forEach(function (x) { x.tabIndex = -1; }); n.tabIndex = 0; n.focus(); }
            });
        });
        ta.addEventListener('keydown', function (e) {
            if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) return;
            var k = e.key.toLowerCase();
            if (k === 'b' || k === 'i' || k === 'k') {
                e.preventDefault();
                actions[k === 'b' ? 'bold' : (k === 'i' ? 'italic' : 'link')]();
            }
        });

        // Widoki: Pisanie / Podgląd / Obok siebie; podgląd renderuje serwer (ten sam kod co zapis).
        var VIEW_KEY = 'feer-blog-md-view';
        var panes = md.querySelector('[data-md-panes]');
        var previewBody = md.querySelector('[data-md-preview-body]');
        var viewButtons = md.querySelectorAll('[data-md-view]');
        var previewTimer = null, lastPreview = null, previewSeq = 0;
        function setView(v) {
            if (v === 'split' && !window.matchMedia('(min-width: 1024px)').matches) v = 'write';
            panes.dataset.view = v;
            viewButtons.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.mdView === v ? 'true' : 'false'); });
            storage.set(VIEW_KEY, v);
            if (v !== 'write') refreshPreview(true);
            autoGrow();
        }
        viewButtons.forEach(function (b) { b.addEventListener('click', function () { setView(b.dataset.mdView); }); });
        function refreshPreview(now) {
            clearTimeout(previewTimer);
            if (panes.dataset.view === 'write') return;
            previewTimer = setTimeout(function () {
                if (ta.value === lastPreview) return;
                var seq = ++previewSeq, text = ta.value;
                fetch(md.dataset.previewUrl, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ markdown: text }),
                }).then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                    .then(function (data) {
                        if (seq !== previewSeq) return;
                        lastPreview = text;
                        // innerHTML nie uruchamia <script>; serwer i tak usuwa skrypty i atrybuty on*.
                        previewBody.innerHTML = data.html || '<p class="text-muted">Brak treści.</p>';
                        updateStats(data.words);
                    })
                    .catch(function () { previewBody.innerHTML = '<p class="font-bold text-red-700">Nie udało się wczytać podglądu.</p>'; });
            }, now ? 0 : 600);
        }

        // Kopia robocza w przeglądarce (jak w edytorze bloków).
        var draftTimer = null;
        ta.addEventListener('input', function () {
            autoGrow();
            updateStats();
            refreshPreview(false);
            clearTimeout(draftTimer);
            draftTimer = setTimeout(function () { storage.set(DRAFT_KEY, JSON.stringify({ at: Date.now(), md: ta.value })); }, 800);
        });
        form.addEventListener('submit', function () { clearTimeout(draftTimer); storage.del(DRAFT_KEY); });
        (function offerDraft() {
            var box = md.querySelector('[data-md-draft]');
            var d = null;
            try { d = JSON.parse(storage.get(DRAFT_KEY) || 'null'); } catch (e) { d = null; }
            if (!d || typeof d.md !== 'string' || d.md === ta.value || !d.md.trim()) return;
            box.querySelector('[data-md-draft-time]').textContent = new Date(d.at).toLocaleString('pl-PL');
            box.hidden = false;
            box.querySelector('[data-md-draft-restore]').addEventListener('click', function () {
                ta.focus();
                ta.select();
                replaceRange(0, ta.value.length, d.md, 0, 0);
                box.hidden = true;
                ta.focus();
            });
            box.querySelector('[data-md-draft-discard]').addEventListener('click', function () {
                storage.del(DRAFT_KEY);
                box.hidden = true;
                ta.focus();
            });
        })();

        var savedView = storage.get(VIEW_KEY);
        setView(['write', 'preview', 'split'].indexOf(savedView) !== -1 ? savedView : 'write');
        updateStats();
        // Pole w ukrytej zakładce ma wysokość 0 — dopasuj po pokazaniu.
        tabs.forEach(function (t) { t.addEventListener('click', autoGrow); });
        setTimeout(autoGrow, 0);
    }

    // ── Przełączanie formatu treści ──
    function setFormat(f) {
        formatInput.value = f;
        form.querySelectorAll('[data-format-pane]').forEach(function (p) { p.hidden = p.dataset.formatPane !== f; });
        if (sideFormat) sideFormat.textContent = f === 'markdown' ? 'Markdown' : 'Wizualny (HTML)';
    }
    var toMd = form.querySelector('[data-switch-to-markdown]');
    if (toMd) {
        toMd.addEventListener('click', function () {
            confirmBox('Przełączyć treść na Markdown?\n\nObecny HTML zostanie zamieniony na Markdown. Proste formatowanie (nagłówki, listy, linki, tabele) przejdzie bez zmian, a nietypowe bloki zostaną jako HTML. Do zapisu możesz wrócić do edytora wizualnego bez utraty dotychczasowej treści.')
                .then(function (ok) {
                    if (!ok) return;
                    var get = window['__getContent_editor-body'];
                    var html = get ? get() : (document.getElementById('editor-body') || {}).value || '';
                    toMd.disabled = true;
                    return fetch(@json(route('admin.wiem-feer.html-na-markdown')), {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ html: html }),
                    }).then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                        .then(function (data) {
                            setFormat('markdown');
                            ta.value = data.markdown || '';
                            ta.dispatchEvent(new Event('input', { bubbles: true }));
                            formatStatus.textContent = 'Treść przełączona na Markdown. Zapisz artykuł, aby zachować zmianę.';
                            ta.focus();
                        })
                        .catch(function () { window.alert('Nie udało się przekonwertować treści. Spróbuj ponownie.'); })
                        .finally(function () { toMd.disabled = false; });
                });
        });
    }
    var toHtml = form.querySelector('[data-switch-to-html]');
    if (toHtml) {
        toHtml.addEventListener('click', function () {
            var noWysiwyg = toHtml.hasAttribute('data-no-wysiwyg');
            var msg = noWysiwyg
                ? 'Przełączyć na edytor wizualny?\n\nPo zapisaniu treść zostanie zamieniona na HTML i otworzy się w edytorze wizualnym.'
                : 'Wrócić do edytora wizualnego?\n\nZmiany wprowadzone w Markdown nie zostaną przeniesione — zostanie dotychczasowa treść z edytora wizualnego.';
            confirmBox(msg).then(function (ok) {
                if (!ok) return;
                if (noWysiwyg) {
                    formatInput.value = 'html';
                    if (sideFormat) sideFormat.textContent = 'Wizualny (HTML) — po zapisie';
                    form.querySelector('[data-no-wysiwyg-note]').hidden = false;
                    toHtml.hidden = true;
                    formatStatus.textContent = 'Po zapisaniu treść otworzy się w edytorze wizualnym.';
                    return;
                }
                setFormat('html');
                formatStatus.textContent = 'Przywrócono edytor wizualny.';
                var t = form.querySelector('[data-switch-to-markdown]');
                if (t) t.focus();
            });
        });
    }
})();
</script>
@endsection
