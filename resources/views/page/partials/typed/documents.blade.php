{{--
    Typ „Dokumenty": grupy odnośników do plików i stron (z rodzajem pliku) + pliki wgrane do strony.
    Płasko: wiersze na szarym tle, ikona pliku bez tła, rodzaj pliku zwykłym tekstem
    (bez kolorowych etykiet). Menu działu w kolumnie bocznej, gdy jest.
--}}
@php
    $td = $page->typeData();
    $docs = collect($td['docs'])->filter(fn ($d) => filled($d['title'] ?? null) && filled($d['url'] ?? null));
    $groups = $docs->groupBy(fn ($d) => trim((string) ($d['group'] ?? '')));
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $link = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="documents">
    @include('page.partials.typed._head')

    <div class="grid gap-10 {{ $localNav ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($localNav)
            <aside class="lg:order-last" aria-label="Menu działu">
                <div class="lg:sticky lg:top-24">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
            @endif

            @foreach ($groups as $gName => $items)
                @php $gid = 'td-g-'.$loop->index; @endphp
                <section class="{{ $loop->first ? '' : 'mt-10' }}" @if ($gName !== '') aria-labelledby="{{ $gid }}" @else aria-label="Dokumenty" @endif>
                    @if ($gName !== '')<h2 id="{{ $gid }}" class="mb-4 text-2xl font-bold text-ink">{{ $gName }}</h2>@endif
                    <ul role="list" class="space-y-2">
                        @foreach ($items as $d)
                            @php
                                $ext = strtolower(pathinfo(parse_url($d['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                                $fileIcon = match (true) {
                                    $ext === 'pdf' => 'fa-file-pdf',
                                    in_array($ext, ['doc', 'docx', 'odt', 'rtf']) => 'fa-file-word',
                                    in_array($ext, ['xls', 'xlsx', 'ods', 'csv']) => 'fa-file-excel',
                                    in_array($ext, ['ppt', 'pptx', 'odp']) => 'fa-file-powerpoint',
                                    in_array($ext, ['zip', 'rar', '7z']) => 'fa-file-zipper',
                                    default => 'fa-link',
                                };
                                $isFile = $ext !== '' && $ext !== 'html';
                                $kindLabel = $isFile ? strtoupper(\Illuminate\Support\Str::limit($ext, 4, '')) : 'strona internetowa';
                                $external = \Illuminate\Support\Str::startsWith($d['url'], 'http');
                            @endphp
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-md bg-gray-50 px-4 py-3">
                                <i class="fa-solid {{ $isFile ? $fileIcon : 'fa-link' }} w-6 flex-none text-center text-xl text-brand-dark" aria-hidden="true"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-ink [overflow-wrap:anywhere]">{{ $d['title'] }}</p>
                                    <p class="text-sm text-muted">{{ $kindLabel }}@if (filled($d['note'] ?? null)) · {{ $d['note'] }}@endif</p>
                                </div>
                                <a href="{{ $d['url'] }}" class="{{ $link }}" @if ($isFile) download @endif @if ($external) target="_blank" rel="noopener" @endif>
                                    <i class="fa-solid {{ $isFile ? 'fa-arrow-down' : 'fa-arrow-up-right-from-square' }}" aria-hidden="true"></i> {{ $isFile ? 'Pobierz' : 'Otwórz' }}<span class="sr-only"> {{ $isFile ? 'plik' : 'stronę' }}: {{ $d['title'] }}{{ $external ? ' (otwiera się w nowej karcie)' : '' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
            @if ($docs->isEmpty() && $page->attachments->isEmpty())
                <p class="text-muted">Dokumenty zostaną wkrótce dodane.</p>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
