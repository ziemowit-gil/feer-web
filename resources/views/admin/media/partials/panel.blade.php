{{--
    Szuflada szczegółów pliku (ładowana do listy przez fetch z admin.multimedia.panel).
    Wszystkie zmiany to zwykłe formularze — po zapisie lista otwiera szufladę z powrotem (flash media_panel).
--}}
@php
    $displayName = $item->getCustomProperty('display_name') ?: $item->file_name;
    $ext = strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION));
    $isImage = $item->isImage();
    $dims = $isImage && $item->mime_type !== 'image/svg+xml' ? (@getimagesize($item->getPath()) ?: null) : null;
    $report = $item->a11y_report;
    $statusText = [
        'ok' => 'Bez zastrzeżeń',
        'warn' => 'Drobne uwagi',
        'fail' => 'Wymaga poprawy',
        'error' => 'Nie udało się sprawdzić',
    ];
    $section = 'rounded-md bg-gray-50 p-4';
    $h = 'mb-2 text-sm font-bold text-ink';
    $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
    $input = 'w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
@endphp

<div class="space-y-5">
    {{-- Podgląd --}}
    <div class="overflow-hidden rounded-md bg-gray-100">
        @if ($isImage)
            <img src="{{ $item->getUrl() }}" alt="{{ $alt }}" class="mx-auto max-h-72 w-auto object-contain">
        @else
            <div class="flex h-40 flex-col items-center justify-center gap-2 text-ink">
                <i class="fa-solid {{ $item->mime_type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file-lines' }} text-5xl text-brand-dark" aria-hidden="true"></i>
                <span class="text-sm font-bold">{{ $ext }}</span>
            </div>
        @endif
    </div>

    <div>
        <h2 id="media-panel-title" class="break-words text-lg font-bold leading-snug text-ink">{{ $displayName }}</h2>
        <p class="mt-1 text-sm text-muted">
            {{ $ext }} · {{ $item->human_readable_size }}
            @if ($dims) · {{ $dims[0] }}×{{ $dims[1] }} px @endif
            · dodano {{ $item->created_at->format('d.m.Y') }}
            @if ($item->uploadedBy) przez {{ $item->uploadedBy->name }} @endif
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ $item->getUrl() }}" target="_blank" rel="noopener" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Otwórz<span class="sr-only"> plik w nowej karcie</span>
            </a>
            <button type="button" class="{{ $btn }} bg-white text-ink hover:bg-brand-light"
                x-data="{ done: false }"
                @click="navigator.clipboard.writeText(@js($item->getUrl())).then(() => { done = true; setTimeout(() => done = false, 2000) })">
                <i class="fa-solid" :class="done ? 'fa-check' : 'fa-link'" aria-hidden="true"></i>
                <span x-text="done ? 'Skopiowano adres' : 'Kopiuj adres'">Kopiuj adres</span>
            </button>
        </div>
        <p class="sr-only" aria-live="polite"></p>
    </div>

    {{-- Gdzie użyto --}}
    <section class="{{ $section }}" aria-labelledby="mp-usage">
        <h3 id="mp-usage" class="{{ $h }}">Gdzie użyto</h3>
        @if (empty($places))
            <p class="text-sm text-ink">Plik nie jest nigdzie używany w treściach serwisu.</p>
        @else
            <ul class="space-y-1.5 text-sm" role="list">
                @foreach ($places as $place)
                    <li>
                        <span class="text-muted">{{ $place['label'] }}:</span>
                        @if ($place['url'])
                            <a href="{{ $place['url'] }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $place['title'] }}</a>
                        @else
                            <span class="font-bold text-ink">{{ $place['title'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Dostępność dokumentu --}}
    @if ($item->isCheckableDocument())
        <section class="{{ $section }}" aria-labelledby="mp-a11y">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 id="mp-a11y" class="{{ $h }}">Dostępność dokumentu</h3>
                <p class="text-sm font-bold {{ ($report['status'] ?? '') === 'ok' ? 'text-brand-dark' : 'text-ink' }}">
                    {{ $statusText[$report['status'] ?? ''] ?? 'Niesprawdzony' }}
                </p>
            </div>
            @if (! empty($report['message']))
                <p class="text-sm text-ink">{{ $report['message'] }}</p>
            @endif
            <ul class="mt-1 space-y-2 text-sm" role="list">
                @foreach ($report['checks'] ?? [] as $check)
                    <li class="flex gap-2">
                        <i class="fa-solid {{ $check['ok'] ? 'fa-circle-check text-brand-dark' : ($check['required'] ? 'fa-circle-xmark text-ink' : 'fa-circle-exclamation text-ink') }} mt-0.5 flex-none" aria-hidden="true"></i>
                        <span>
                            <span class="font-bold text-ink">{{ $check['label'] }}</span>
                            <span class="sr-only">— {{ $check['ok'] ? 'w porządku' : ($check['required'] ? 'brak' : 'zalecane') }}</span>
                            @if ($check['value']) <span class="text-muted">({{ $check['value'] }})</span> @endif
                            @unless ($check['ok'])
                                <span class="block text-ink">{{ $check['hint'] }}</span>
                            @endunless
                        </span>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.multimedia.recheck', $item) }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Sprawdź ponownie</button>
            </form>
        </section>
    @endif

    {{-- Opis i dane --}}
    <section class="{{ $section }} space-y-4" aria-labelledby="mp-meta">
        <h3 id="mp-meta" class="{{ $h }}">Opis i porządek</h3>

        <form method="POST" action="{{ route('admin.multimedia.rename', $item) }}">
            @csrf @method('PUT')
            <label for="mp-name" class="mb-1 block text-sm font-bold text-ink">Nazwa wyświetlana</label>
            <div class="flex gap-2">
                <input id="mp-name" name="name" type="text" value="{{ $displayName }}" required maxlength="255" class="{{ $input }}">
                <button type="submit" class="{{ $btn }} flex-none bg-white text-ink hover:bg-brand-light">Zapisz</button>
            </div>
            <p class="mt-1 text-xs text-muted">Nie zmienia adresu pliku.</p>
        </form>

        @if ($isImage)
            <form method="POST" action="{{ route('admin.multimedia.alt', $item) }}">
                @csrf @method('PUT')
                <label for="mp-alt" class="mb-1 block text-sm font-bold text-ink">Opis alternatywny</label>
                <div class="flex gap-2">
                    <input id="mp-alt" name="alt" type="text" value="{{ $item->getCustomProperty('alt') }}" maxlength="255"
                        placeholder="Co przedstawia obraz?" class="{{ $input }}">
                    <button type="submit" class="{{ $btn }} flex-none bg-white text-ink hover:bg-brand-light">Zapisz</button>
                </div>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.multimedia.author', $item) }}">
            @csrf @method('PUT')
            <label for="mp-author" class="mb-1 block text-sm font-bold text-ink">Autor / twórca</label>
            <div class="flex gap-2">
                <input id="mp-author" name="author" type="text" value="{{ $item->getCustomProperty('author') }}" maxlength="255"
                    placeholder="np. Jan Kowalski" class="{{ $input }}">
                <button type="submit" class="{{ $btn }} flex-none bg-white text-ink hover:bg-brand-light">Zapisz</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.multimedia.tags', $item) }}">
            @csrf @method('PUT')
            <label for="mp-tags" class="mb-1 block text-sm font-bold text-ink">Tagi</label>
            <div class="flex gap-2">
                <input id="mp-tags" name="tags_text" type="text" value="{{ implode(', ', $item->getCustomProperty('tags', [])) }}"
                    aria-describedby="mp-tags-help" class="{{ $input }}">
                <button type="submit" class="{{ $btn }} flex-none bg-white text-ink hover:bg-brand-light">Zapisz</button>
            </div>
            <p id="mp-tags-help" class="mt-1 text-xs text-muted">Oddziel przecinkami, np. „szkolenia, 2026”.</p>
        </form>

        <form method="POST" action="{{ route('admin.multimedia.move', $item) }}">
            @csrf @method('PUT')
            <label for="mp-folder" class="mb-1 block text-sm font-bold text-ink">Folder</label>
            <div class="flex gap-2">
                <select id="mp-folder" name="folder_id" class="{{ $input }}">
                    <option value="">— katalog główny —</option>
                    @foreach ($allFolders as $option)
                        <option value="{{ $option->id }}" @selected($item->media_folder_id === $option->id)>{{ $option->fullPath() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="{{ $btn }} flex-none bg-white text-ink hover:bg-brand-light">Przenieś</button>
            </div>
        </form>
    </section>

    {{-- Podmiana i wersje --}}
    <section class="{{ $section }}" aria-labelledby="mp-replace">
        <h3 id="mp-replace" class="{{ $h }}">Nowa wersja pliku</h3>
        <p class="text-sm text-ink">Wgraj poprawiony plik — adres zostanie ten sam, więc wszystkie odnośniki dalej zadziałają. Obecna wersja trafi do historii.</p>
        <form method="POST" action="{{ route('admin.multimedia.replace', $item) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
            @csrf
            <div>
                <label for="mp-file" class="mb-1 block text-sm font-bold text-ink">Plik .{{ strtolower($ext) }}</label>
                <input id="mp-file" name="file" type="file" required accept=".{{ strtolower($ext) }}{{ in_array($ext, ['JPG', 'JPEG'], true) ? ',.jpg,.jpeg' : '' }}"
                    class="block w-full text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:font-bold file:text-ink hover:file:bg-brand-light">
            </div>
            <div>
                <label for="mp-note" class="mb-1 block text-sm font-bold text-ink">Co się zmieniło? <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input id="mp-note" name="note" type="text" maxlength="500" placeholder="np. aktualizacja regulaminu od 1 listopada" class="{{ $input }}">
            </div>
            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"></i> Podmień plik
            </button>
        </form>

        @if ($item->versions->isNotEmpty())
            <h4 class="mb-2 mt-5 text-sm font-bold text-ink">Historia wersji</h4>
            <ol class="space-y-2" role="list">
                @foreach ($item->versions as $version)
                    <li class="rounded-md bg-white p-3 text-sm">
                        <p class="font-bold text-ink">{{ $version->created_at->format('d.m.Y, H:i') }}</p>
                        <p class="text-muted">
                            {{ $version->humanSize() }}
                            @if ($version->replacedBy) · podmienił(a) {{ $version->replacedBy->name }} @endif
                        </p>
                        @if ($version->note)<p class="text-ink">{{ $version->note }}</p>@endif
                        <div class="mt-2 flex flex-wrap gap-3">
                            <a href="{{ route('admin.multimedia.versions.download', [$item, $version]) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">
                                Pobierz<span class="sr-only"> wersję z {{ $version->created_at->format('d.m.Y, H:i') }}</span>
                            </a>
                            <form method="POST" action="{{ route('admin.multimedia.versions.restore', [$item, $version]) }}"
                                onsubmit="return confirm('Przywrócić tę wersję? Obecna trafi do historii.')">
                                @csrf
                                <button type="submit" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">
                                    Przywróć<span class="sr-only"> wersję z {{ $version->created_at->format('d.m.Y, H:i') }}</span>
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    {{-- Archiwum i usuwanie --}}
    <section class="flex flex-wrap gap-2" aria-label="Archiwum i usuwanie">
        @if ($item->archived_at)
            <form method="POST" action="{{ route('admin.multimedia.restore', $item) }}">
                @csrf @method('PUT')
                <button type="submit" class="{{ $btn }} bg-white text-ink hover:bg-brand-light"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć z archiwum</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.multimedia.archive', $item) }}">
                @csrf @method('PUT')
                <button type="submit" class="{{ $btn }} bg-white text-ink hover:bg-brand-light"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Schowaj do archiwum</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.multimedia.destroy', $item) }}"
            onsubmit="return confirm(@js('Usunąć plik „'.$displayName.'” na stałe?'))">
            @csrf @method('DELETE')
            <button type="submit" class="{{ $btn }} bg-white text-red-800 hover:bg-red-50"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
        </form>
    </section>
</div>
