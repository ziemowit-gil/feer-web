@extends('admin.layout')

@section('title', 'Oś czasu')

@section('content')
    @if (! $page)
        <div class="max-w-3xl rounded-md bg-amber-50 p-6 text-sm text-amber-800">
            <p class="font-bold">Brak strony typu „O organizacji".</p>
            <p class="mt-1">Oś czasu jest częścią strony „O organizacji". Utwórz taką stronę
                (<a href="{{ route('admin.podstrony.create') }}" class="font-bold underline">Dodaj stronę</a> →
                typ „O organizacji"), a następnie wróć tutaj, aby wygodnie edytować historię.</p>
        </div>
    @else
        @php $aboutTimeline = array_values((array) old('about_timeline', $page->about_timeline ?? [])); @endphp

        <form method="POST" action="{{ route('admin.os-czasu.update', $page) }}" class="max-w-3xl space-y-5">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 rounded-md bg-white">
                <div>
                    <p class="text-sm text-muted">Edytujesz oś czasu strony:</p>
                    <p class="font-bold">{{ $page->title }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($pages->count() > 1)
                        <label class="flex items-center gap-2 text-sm">
                            <span class="text-muted">Strona:</span>
                            <select onchange="window.location = '{{ route('admin.os-czasu.edit') }}?page=' + this.value"
                                class="rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                @foreach ($pages as $option)
                                    <option value="{{ $option->id }}" @selected($option->id === $page->id)>{{ $option->title }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <a href="{{ route('admin.podstrony.edit', $page) }}" class="text-sm text-brand-dark underline">Pełna edycja strony</a>
                </div>
            </div>

            <div class="p-6 rounded-md bg-white" data-repeater>
                <p class="mb-4 text-xs text-muted">Rok / etap + opis, opcjonalny link oraz kolor znacznika na osi. Puste wiersze są pomijane; kolejność na liście odpowiada kolejności na stronie.</p>

                <div data-repeater-rows class="space-y-3">
                    @foreach ($aboutTimeline as $i => $row)
                        <div data-repeater-row class="space-y-2 p-4 rounded-md bg-gray-50">
                            <div class="grid gap-2 sm:grid-cols-[1fr_3fr]">
                                <input type="text" name="about_timeline[{{ $i }}][year]" value="{{ $row['year'] ?? '' }}" placeholder="Rok, np. 2015" aria-label="Rok wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                <input type="text" name="about_timeline[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis wydarzenia" aria-label="Opis wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <p class="text-xs font-medium text-muted">Linki zewnętrzne (maks. 3)</p>
                            @for ($l = 1; $l <= 3; $l++)
                                @php $lk = $l === 1 ? '' : $l; @endphp
                                <div class="grid gap-2 sm:grid-cols-[3fr_2fr]">
                                    <input type="url" name="about_timeline[{{ $i }}][url{{ $lk }}]" value="{{ $row['url'.$lk] ?? '' }}" placeholder="Link {{ $l }} (URL)" aria-label="Link {{ $l }} wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                    <input type="text" name="about_timeline[{{ $i }}][label{{ $lk }}]" value="{{ $row['label'.$lk] ?? '' }}" placeholder="Etykieta linku {{ $l }}" aria-label="Etykieta linku {{ $l }} wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                </div>
                            @endfor
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex items-center gap-2 text-xs text-muted">Kolor znacznika
                                    <input type="color" name="about_timeline[{{ $i }}][color]" value="{{ $row['color'] ?? $siteSettings->brand_color }}" aria-label="Kolor znacznika na osi czasu {{ $i + 1 }}" class="h-8 w-12 rounded border-gray-500">
                                </label>
                                <div class="flex items-center gap-1"><button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand-dark" aria-label="Przenieś wpis wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button><button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand-dark" aria-label="Przenieś wpis niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button><button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" data-repeater-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand-dark px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis</button>

                <template data-repeater-template>
                    <div data-repeater-row class="space-y-2 p-4 rounded-md bg-gray-50">
                        <div class="grid gap-2 sm:grid-cols-[1fr_3fr]">
                            <input type="text" name="about_timeline[__INDEX__][year]" placeholder="Rok, np. 2015" aria-label="Rok wpisu osi czasu" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <input type="text" name="about_timeline[__INDEX__][text]" placeholder="Opis wydarzenia" aria-label="Opis wpisu osi czasu" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        <p class="text-xs font-medium text-muted">Linki zewnętrzne (maks. 3)</p>
                        @for ($l = 1; $l <= 3; $l++)
                            @php $lk = $l === 1 ? '' : $l; @endphp
                            <div class="grid gap-2 sm:grid-cols-[3fr_2fr]">
                                <input type="url" name="about_timeline[__INDEX__][url{{ $lk }}]" placeholder="Link {{ $l }} (URL)" aria-label="Link {{ $l }} wpisu osi czasu" class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                <input type="text" name="about_timeline[__INDEX__][label{{ $lk }}]" placeholder="Etykieta linku {{ $l }}" aria-label="Etykieta linku {{ $l }} wpisu osi czasu" class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                        @endfor
                        <div class="flex items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-xs text-muted">Kolor znacznika
                                <input type="color" name="about_timeline[__INDEX__][color]" value="{{ $siteSettings->brand_color }}" aria-label="Kolor znacznika na osi czasu" class="h-8 w-12 rounded border-gray-500">
                            </label>
                            <div class="flex items-center gap-1"><button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand-dark" aria-label="Przenieś wpis wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button><button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand-dark" aria-label="Przenieś wpis niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button><button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
                <a href="{{ $page->slug ? url($page->slug) : route('admin.podstrony.index') }}" target="_blank" rel="noopener" class="text-sm text-brand-dark underline">Podgląd strony</a>
            </div>
        </form>

        <script>
            document.querySelectorAll('[data-repeater]').forEach(function (rep) {
                const rows = rep.querySelector('[data-repeater-rows]');
                const template = rep.querySelector('[data-repeater-template]');
                const addBtn = rep.querySelector('[data-repeater-add]');
                if (!rows || !template) return;
                let nextIndex = rows.querySelectorAll('[data-repeater-row]').length;

                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = html.trim();
                        rows.appendChild(wrapper.firstElementChild);
                    });
                }

                // Przelicz indeksy nazw pól wg kolejności w DOM, aby zapisana
                // kolejność odpowiadała tej na ekranie (po przenoszeniu wierszy).
                const reindex = function () {
                    rows.querySelectorAll('[data-repeater-row]').forEach(function (row, n) {
                        row.querySelectorAll('[name]').forEach(function (el) {
                            el.name = el.name.replace(/^([^\[]+)\[[^\]]*\]/, '$1[' + n + ']');
                        });
                    });
                };

                rep.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-repeater-remove]');
                    if (remove) {
                        const row = remove.closest('[data-repeater-row]');
                        if (row) row.remove();
                        return;
                    }
                    const move = e.target.closest('[data-repeater-move]');
                    if (move) {
                        const row = move.closest('[data-repeater-row]');
                        if (!row) return;
                        if (move.dataset.repeaterMove === 'up' && row.previousElementSibling) {
                            rows.insertBefore(row, row.previousElementSibling);
                        } else if (move.dataset.repeaterMove === 'down' && row.nextElementSibling) {
                            rows.insertBefore(row.nextElementSibling, row);
                        }
                        reindex();
                    }
                });
            });
        </script>
    @endif
@endsection
