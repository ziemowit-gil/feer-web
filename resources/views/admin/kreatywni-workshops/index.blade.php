@extends('admin.layout')

@section('title', 'Warsztaty – Kreatywni')

{{--
    Mikro moduł „Warsztaty – Kreatywni” (KreatywniWorkshopController): terminy warsztatów do strony typu „Kalendarz
    warsztatów”. Nadchodzące od najbliższego, minione od najnowszego (do 50). „Kopiuj termin” otwiera formularz nowego
    terminu z tymi samymi danymi. Cele ≥ 44 px, kontrast ink/muted na bieli.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl space-y-2 text-sm text-ink">
            <p>Terminy warsztatów: nazwa, miejsce, data i godzina, liczba uczestników, opis i grafika. Pokazują się na stronie typu
                „Kalendarz warsztatów” (Strony → Dodaj stronę → typ „Kalendarz warsztatów”) — od najbliższego; miniony termin sam
                znika z listy nadchodzących.</p>
            @if ($calendarPages->isNotEmpty())
                <p>Strona z kalendarzem:
                    @foreach ($calendarPages as $p)
                        <a href="{{ $p->publicUrl() }}" class="font-bold text-brand-dark underline underline-offset-2">{{ $p->title }}</a>{{ $p->is_published ? '' : ' (szkic)' }}{{ $loop->last ? '' : ',' }}
                    @endforeach
                </p>
            @else
                <p class="rounded-md bg-amber-50 px-3 py-2 text-amber-900">
                    <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                    Nie ma jeszcze strony z kalendarzem. Dodaj stronę typu „Kalendarz warsztatów” w
                    <a href="{{ route('admin.podstrony.index') }}" class="font-bold underline underline-offset-2">Stronach</a>, żeby terminy było widać w serwisie.
                </p>
            @endif
        </div>
        <a href="{{ route('admin.kreatywni-warsztaty.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj warsztat
        </a>
    </div>

    @foreach (['upcoming' => ['Nadchodzące i trwające', $upcoming, 'Nie ma zaplanowanych warsztatów.'], 'past' => ['Minione', $past, 'Nie ma minionych warsztatów.']] as $key => [$heading, $list, $empty])
        <section class="mb-8" aria-labelledby="kw-{{ $key }}">
            <h2 id="kw-{{ $key }}" class="mb-3 text-base font-bold text-ink">{{ $heading }} <span class="font-normal text-muted">({{ $list->count() }})</span></h2>
            @if ($list->isEmpty())
                <p class="rounded-md bg-white px-4 py-5 text-sm text-ink">{{ $empty }}</p>
            @else
                <div class="overflow-x-auto rounded-md bg-white">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <caption class="sr-only">{{ $heading }} warsztaty: termin, nazwa i miejsce, uczestnicy, widoczność i działania</caption>
                        <thead class="bg-gray-50 text-ink">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-bold">Termin</th>
                                <th scope="col" class="px-4 py-3 font-bold">Warsztat</th>
                                <th scope="col" class="px-4 py-3 font-bold">Uczestnicy</th>
                                <th scope="col" class="px-4 py-3 font-bold">Na stronie</th>
                                <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($list as $w)
                                <tr class="align-middle">
                                    <td class="whitespace-nowrap px-4 py-3 text-ink">
                                        <time datetime="{{ $w->starts_at->toIso8601String() }}">{{ $w->startsLocal()->translatedFormat('j M Y, H:i') }}</time>
                                        @if ($w->endsLocal())<span class="block text-xs text-muted">do {{ $w->startsLocal()->isSameDay($w->endsLocal()) ? $w->endsLocal()->format('H:i') : $w->endsLocal()->translatedFormat('j M, H:i') }}</span>@endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($w->imageUrl())
                                                <img src="{{ $w->imageUrl() }}" alt="" class="h-11 w-16 flex-none rounded object-cover">
                                            @else
                                                <span class="flex h-11 w-16 flex-none items-center justify-center rounded bg-gray-100 text-muted" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                                            @endif
                                            <span>
                                                <span class="block font-bold text-ink">{{ $w->title }}</span>
                                                @if ($w->place)<span class="block text-xs text-muted">{{ $w->place }}</span>@endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-ink">{{ $w->participants ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-ink">
                                        @if ($w->is_active)
                                            <i class="fa-solid fa-eye mr-1 text-muted" aria-hidden="true"></i>Widoczny
                                        @else
                                            <i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>Ukryty
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.kreatywni-warsztaty.edit', $w) }}" class="{{ $iconBtn }}" title="Edytuj">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $w->title }}, {{ $w->startsLocal()->translatedFormat('j F Y') }}</span>
                                            </a>
                                            <a href="{{ route('admin.kreatywni-warsztaty.create', ['kopia' => $w->id]) }}" class="{{ $iconBtn }}" title="Kopiuj termin">
                                                <i class="fa-solid fa-copy" aria-hidden="true"></i><span class="sr-only">Kopiuj termin: {{ $w->title }}</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.kreatywni-warsztaty.destroy', $w) }}" onsubmit="return confirm(@js('Usunąć warsztat „'.$w->title.'” ('.$w->whenLabel().')? Tego nie można cofnąć.'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $iconBtn }} hover:bg-red-50 hover:text-red-700" title="Usuń">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń: {{ $w->title }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
@endsection
