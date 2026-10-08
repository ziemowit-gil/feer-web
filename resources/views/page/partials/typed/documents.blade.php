{{-- Typ „Dokumenty": grupy odnośników do plików i stron (z oznaczeniem typu pliku) + pliki wgrane do strony. --}}
@php
    $td = $page->typeData();
    $docs = collect($td['docs'])->filter(fn ($d) => filled($d['title'] ?? null) && filled($d['url'] ?? null));
    $groups = $docs->groupBy(fn ($d) => trim((string) ($d['group'] ?? '')));
@endphp
@include('partials.attachments-css')
<section class="mx-auto max-w-5xl px-4 py-8">
    @include('page.partials.typed._head')

    @foreach ($groups as $gName => $items)
        @php $gid = 'td-g-'.\Illuminate\Support\Str::random(5); @endphp
        <section @if ($gName !== '') aria-labelledby="{{ $gid }}" @endif>
            @if ($gName !== '')<h2 id="{{ $gid }}" class="mb-3 mt-8 border-l-4 border-brand pl-3 text-2xl font-bold text-ink">{{ $gName }}</h2>@endif
            <ul role="list" class="dl-grid">
                @foreach ($items as $d)
                    @php
                        $ext = strtolower(pathinfo(parse_url($d['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                        [$kind, $fileIcon] = match (true) {
                            $ext === 'pdf' => ['is-pdf', 'fa-file-pdf'],
                            in_array($ext, ['doc', 'docx', 'odt', 'rtf']) => ['is-doc', 'fa-file-word'],
                            in_array($ext, ['xls', 'xlsx', 'ods', 'csv']) => ['is-xls', 'fa-file-excel'],
                            in_array($ext, ['ppt', 'pptx', 'odp']) => ['is-ppt', 'fa-file-powerpoint'],
                            in_array($ext, ['zip', 'rar', '7z']) => ['is-zip', 'fa-file-zipper'],
                            default => ['is-zip', 'fa-link'],
                        };
                        $isFile = $ext !== '' && $ext !== 'html';
                        $ext_ = \Illuminate\Support\Str::startsWith($d['url'], 'http');
                    @endphp
                    <li class="dl-card">
                        <div class="dl-top">
                            <span class="dl-type {{ $kind }}" aria-hidden="true"><i class="fa-solid {{ $fileIcon }}"></i>{{ $isFile ? strtoupper(\Illuminate\Support\Str::limit($ext, 4, '')) : 'LINK' }}</span>
                            <div>
                                <p class="dl-name">{{ $d['title'] }}</p>
                                @if (filled($d['note'] ?? null))<p class="dl-meta">{{ $d['note'] }}</p>@endif
                            </div>
                        </div>
                        <a href="{{ $d['url'] }}" class="dl-btn" @if ($isFile) download @endif @if ($ext_) target="_blank" rel="noopener" @endif>
                            <i class="fa-solid {{ $isFile ? 'fa-arrow-down' : 'fa-arrow-up-right-from-square' }}" aria-hidden="true"></i> {{ $isFile ? 'Pobierz' : 'Otwórz' }}<span class="sr-only"> {{ $isFile ? 'plik' : 'stronę' }}: {{ $d['title'] }}{{ $ext_ ? ' (otwiera się w nowej karcie)' : '' }}</span>
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
