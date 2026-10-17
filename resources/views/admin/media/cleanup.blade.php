@extends('admin.layout')

@section('title', 'Porządki w plikach')

@section('content')
    @php
        $fs = fn ($b) => (int) $b > 0 ? \Illuminate\Support\Number::fileSize((int) $b, 1) : "0 B";
        $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
        $kinds = \App\Http\Controllers\Admin\MediaLibraryController::KINDS;
        $openUrl = fn ($m) => route('admin.multimedia.index', array_filter(['folder' => $m->media_folder_id, 'plik' => $m->id]));
        $name = fn ($m) => $m->getCustomProperty('display_name') ?: $m->file_name;
        $wasted = (int) $duplicates->sum('wasted');
        $unusedBytes = (int) $unused->sum('size');
        $largeBytes = (int) $largeImages->sum(fn ($r) => $r['media']->size);
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold text-ink">Porządki w plikach</h1>
            <p class="mt-1 text-sm text-ink">Co zajmuje miejsce i co można bezpiecznie uprzątnąć. Zanim usuniesz plik, sprawdź w jego szczegółach, gdzie jest używany — albo schowaj go do archiwum, z którego da się go przywrócić.</p>
        </div>
        <a href="{{ route('admin.multimedia.index') }}" class="{{ $btn }} bg-white text-ink hover:bg-brand-light">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do plików
        </a>
    </div>

    {{-- Podsumowanie --}}
    <dl class="mb-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Zajęte miejsce</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $fs($storage['total']) }}</dd>
            <dd class="text-sm text-ink">
                @if ($storage['quota']) z {{ $fs($storage['quota']) }} ({{ min(100, (int) round($storage['total'] / $storage['quota'] * 100)) }}%) · @endif
                {{ $storage['count'] }} {{ trans_choice('plik|pliki|plików', $storage['count']) }}
            </dd>
        </div>
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Duplikaty</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $duplicates->count() }}</dd>
            <dd class="text-sm text-ink">{{ $wasted ? 'do odzyskania '.$fs($wasted) : 'brak powtórzonych plików' }}</dd>
        </div>
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Nieużywane</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $unused->count() }}</dd>
            <dd class="text-sm text-ink">{{ $unused->isNotEmpty() ? 'razem '.$fs($unusedBytes) : 'każdy plik jest gdzieś użyty' }}</dd>
        </div>
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Za duże obrazy</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $largeImages->count() }}</dd>
            <dd class="text-sm text-ink">{{ $largeImages->isNotEmpty() ? 'razem '.$fs($largeBytes) : 'obrazy mają rozsądny rozmiar' }}</dd>
        </div>
    </dl>

    {{-- Miejsce --}}
    <section class="mb-8 rounded-md bg-white p-5" aria-labelledby="sec-storage">
        <h2 id="sec-storage" class="text-lg font-bold text-ink">Na co idzie miejsce</h2>
        <div class="mt-4 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-bold text-ink">Według rodzaju</h3>
                <table class="w-full text-sm">
                    <caption class="sr-only">Zajęte miejsce według rodzaju pliku</caption>
                    <tbody>
                        <tr><th scope="row" class="py-1.5 text-left font-normal text-ink">Oryginały plików</th><td class="py-1.5 text-right font-bold text-ink">{{ $fs($storage['originals']) }}</td></tr>
                        @foreach ($kinds as $k => $label)
                            @if ($storage['by_kind'][$k] ?? null)
                                <tr><th scope="row" class="py-1.5 pl-4 text-left font-normal text-muted">{{ $label }} ({{ $storage['by_kind'][$k]['count'] }})</th><td class="py-1.5 text-right text-ink">{{ $fs($storage['by_kind'][$k]['bytes']) }}</td></tr>
                            @endif
                        @endforeach
                        <tr><th scope="row" class="py-1.5 text-left font-normal text-ink">Miniatury i WebP (tworzone automatycznie)</th><td class="py-1.5 text-right font-bold text-ink">{{ $fs($storage['conversions']) }}</td></tr>
                        <tr><th scope="row" class="py-1.5 text-left font-normal text-ink">Poprzednie wersje plików</th><td class="py-1.5 text-right font-bold text-ink">{{ $fs($storage['versions']) }}</td></tr>
                        @if ($archivedCount)
                            <tr><th scope="row" class="py-1.5 text-left font-normal text-ink">W tym archiwum ({{ $archivedCount }})</th><td class="py-1.5 text-right text-ink">{{ $fs($archivedBytes) }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-bold text-ink">Według folderu</h3>
                <table class="w-full text-sm">
                    <caption class="sr-only">Zajęte miejsce według folderu</caption>
                    <tbody>
                        @foreach ($storage['by_folder']->take(12) as $folderName => $row)
                            <tr>
                                <th scope="row" class="py-1.5 text-left font-normal text-ink">{{ $folderName }} <span class="text-muted">({{ $row['count'] }})</span></th>
                                <td class="py-1.5 text-right text-ink">{{ $fs($row['bytes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Duplikaty --}}
    <section class="mb-8" aria-labelledby="sec-dup">
        <h2 id="sec-dup" class="text-lg font-bold text-ink">Duplikaty</h2>
        <p class="mb-3 text-sm text-ink">Pliki o identycznej treści wgrane kilka razy. Zostaw jeden — najlepiej ten, który jest używany — a pozostałe usuń albo schowaj.</p>
        @forelse ($duplicates as $group)
            <div class="mb-3 rounded-md bg-white p-4">
                <p class="mb-2 text-sm text-ink"><strong>{{ $group['items']->count() }} kopie</strong> · {{ $group['items']->first()->human_readable_size }} każda · do odzyskania {{ $fs($group['wasted']) }}</p>
                <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($group['items'] as $m)
                        <li class="flex items-center gap-3 rounded-md bg-gray-50 p-2">
                            <span class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded bg-gray-100">
                                @if ($m->isImage())<img src="{{ $m->getUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">@else<i class="fa-solid fa-file text-brand-dark" aria-hidden="true"></i>@endif
                            </span>
                            <span class="min-w-0 text-sm">
                                <a href="{{ $openUrl($m) }}" class="block truncate font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $name($m) }}</a>
                                <span class="block text-muted">{{ $m->folder?->fullPath() ?? 'Katalog główny' }} · {{ $m->created_at->format('d.m.Y') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="rounded-md bg-white p-4 text-sm text-ink">Nie znaleziono duplikatów.</p>
        @endforelse
    </section>

    {{-- Nieużywane --}}
    <section class="mb-8" aria-labelledby="sec-unused" x-data="{ selected: [] }">
        <h2 id="sec-unused" class="text-lg font-bold text-ink">Nieużywane pliki</h2>
        <p class="mb-3 text-sm text-ink">Pliki wgrane do biblioteki, do których nie odwołuje się żadna treść w serwisie. Mogą jednak być linkowane z zewnątrz (np. w mailach lub mediach społecznościowych) — w razie wątpliwości schowaj je do archiwum zamiast usuwać.</p>
        @if ($unused->isEmpty())
            <p class="rounded-md bg-white p-4 text-sm text-ink">Każdy plik z biblioteki jest gdzieś używany.</p>
        @else
            <form method="POST" action="{{ route('admin.multimedia.bulk') }}" class="mb-3 flex flex-wrap items-center gap-2">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <span class="text-sm text-ink" aria-live="polite">Zaznaczono: <strong x-text="selected.length">0</strong></span>
                <button type="submit" name="action" value="archive" :disabled="!selected.length" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink disabled:opacity-60">Schowaj zaznaczone do archiwum</button>
                <button type="submit" name="action" value="delete" :disabled="!selected.length"
                    @click="if (! confirm('Usunąć zaznaczone pliki na stałe?')) $event.preventDefault()"
                    class="{{ $btn }} bg-white text-red-800 hover:bg-red-50 disabled:opacity-60">Usuń zaznaczone</button>
                <button type="button" @click="selected = selected.length ? [] : @js($unused->pluck('id')->values())" class="text-sm font-bold text-brand-dark underline underline-offset-2 hover:no-underline">
                    <span x-text="selected.length ? 'Odznacz wszystkie' : 'Zaznacz wszystkie'">Zaznacz wszystkie</span>
                </button>
            </form>
            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($unused as $m)
                    <li class="flex items-center gap-3 rounded-md bg-white p-2" :class="selected.includes({{ $m->id }}) && 'bg-brand-light'">
                        <input type="checkbox" id="unused-{{ $m->id }}" value="{{ $m->id }}" x-model.number="selected" class="h-4 w-4 flex-none rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <label for="unused-{{ $m->id }}" class="sr-only">Zaznacz {{ $name($m) }}</label>
                        <span class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded bg-gray-100">
                            @if ($m->isImage())<img src="{{ $m->getUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">@else<i class="fa-solid fa-file text-brand-dark" aria-hidden="true"></i>@endif
                        </span>
                        <span class="min-w-0 text-sm">
                            <a href="{{ $openUrl($m) }}" class="block truncate font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $name($m) }}</a>
                            <span class="block text-muted">{{ $m->human_readable_size }} · {{ $m->created_at->format('d.m.Y') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Za duże obrazy --}}
    <section aria-labelledby="sec-large">
        <h2 id="sec-large" class="text-lg font-bold text-ink">Za duże obrazy</h2>
        <p class="mb-3 text-sm text-ink">
            Obrazy cięższe niż {{ $fs(\App\Services\Media\MediaCleanup::LARGE_IMAGE_BYTES) }} albo większe niż {{ \App\Services\Media\MediaCleanup::LARGE_IMAGE_PX }} px.
            Serwis i tak pokazuje je jako lżejsze WebP, ale oryginał zajmuje miejsce. Po otwarciu Multimediów serwis sam zmniejsza je w tle
            do {{ \App\Services\Media\MediaOptimizer::MAX_PX }} px (JPG, PNG, WebP; do {{ \App\Jobs\ShrinkLargeImages::LIMIT }} naraz) — adres się nie zmienia, a poprzednia wersja zostaje w szczegółach pliku.
            GIF i SVG zmniejsz ręcznie i podmień w szczegółach pliku.
        </p>
        @if ($largeImages->isEmpty())
            <p class="rounded-md bg-white p-4 text-sm text-ink">Wszystkie obrazy mają rozsądny rozmiar.</p>
        @else
            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($largeImages as $row)
                    @php $m = $row['media']; @endphp
                    <li class="flex items-center gap-3 rounded-md bg-white p-2">
                        <span class="h-12 w-12 flex-none overflow-hidden rounded bg-gray-100">
                            <img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </span>
                        <span class="min-w-0 text-sm">
                            <a href="{{ $openUrl($m) }}" class="block truncate font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $name($m) }}</a>
                            <span class="block text-ink">{{ implode(' · ', $row['reasons']) }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
