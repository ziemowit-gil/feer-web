{{-- Typ „Dokumenty": grupy odnośników do plików i stron (z oznaczeniem typu pliku) + pliki wgrane do strony. --}}
@php
    $td = $page->typeData();
    $docs = collect($td['docs'])->filter(fn ($d) => filled($d['title'] ?? null) && filled($d['url'] ?? null));
    $groups = $docs->groupBy(fn ($d) => trim((string) ($d['group'] ?? '')));
@endphp
@once
    <style>
        .td-list { list-style: none; margin: 0 0 1.5rem; padding: 0; display: grid; gap: .5rem; }
        .td-item a { display: flex; align-items: center; gap: .85rem; padding: .75rem 1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; color: #1d1d1a; text-decoration: none; }
        .td-item a:hover { border-color: var(--color-brand); background: #fff; } .td-item a:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
        .td-ext { display: inline-flex; flex: none; align-items: center; justify-content: center; min-width: 3rem; height: 2.25rem; border-radius: .375rem; background: #1d1d1a; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; }
        .td-ext.is-pdf { background: #b91c1c; } .td-ext.is-doc { background: #1d4ed8; } .td-ext.is-xls { background: #166534; } .td-ext.is-link { background: #4b5563; }
        .td-title { font-weight: 800; } .td-note { display: block; font-size: .9rem; color: #374151; }
    </style>
@endonce
<section class="mx-auto max-w-5xl px-4 py-8">
    @include('page.partials.typed._head')

    @foreach ($groups as $gName => $items)
        @php $gid = 'td-g-'.\Illuminate\Support\Str::random(5); @endphp
        <section @if ($gName !== '') aria-labelledby="{{ $gid }}" @endif>
            @if ($gName !== '')<h2 id="{{ $gid }}" class="mb-3 mt-8 border-l-4 border-brand pl-3 text-2xl font-bold text-ink">{{ $gName }}</h2>@endif
            <ul role="list" class="td-list">
                @foreach ($items as $d)
                    @php
                        $ext = strtolower(pathinfo(parse_url($d['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                        $kind = match (true) { $ext === 'pdf' => 'is-pdf', in_array($ext, ['doc', 'docx', 'odt']) => 'is-doc', in_array($ext, ['xls', 'xlsx', 'ods', 'csv']) => 'is-xls', $ext === '' => 'is-link', default => '' };
                        $isFile = $ext !== '' && $ext !== 'html';
                    @endphp
                    <li class="td-item">
                        <a href="{{ $d['url'] }}" @if ($isFile) download @endif @if (\Illuminate\Support\Str::startsWith($d['url'], 'http')) target="_blank" rel="noopener" @endif>
                            <span class="td-ext {{ $kind }}" aria-hidden="true">{{ $ext !== '' ? strtoupper($ext) : 'LINK' }}</span>
                            <span class="min-w-0"><span class="td-title">{{ $d['title'] }}</span>@if (filled($d['note'] ?? null))<span class="td-note">{{ $d['note'] }}</span>@endif</span>
                            <span class="sr-only">{{ $isFile ? '(plik '.strtoupper($ext).')' : '' }}{{ \Illuminate\Support\Str::startsWith($d['url'], 'http') ? ' (otwiera się w nowej karcie)' : '' }}</span>
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
</section>
