@extends('admin.layout')

@section('title', 'Kategorie aktualności')

@php $swatches = \App\Support\ThemePalette::swatches(); @endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Kategorie aktualności</h1>
        <p class="text-sm text-muted">Porządkują aktualności na stronie (filtr i odznaki na kartach). Kolejność z tej listy obowiązuje też w filtrze na stronie /aktualnosci.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status"><i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ session('error') }}</div>
    @endif

    {{-- Szybkie dodawanie --}}
    <form method="POST" action="{{ route('admin.kategorie-newsow.store') }}" x-data="{ color: '' }" class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
        @csrf
        <h2 class="mb-3 text-sm font-bold text-ink">Dodaj kategorię</h2>
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[14rem] flex-1">
                <label for="qc-name" class="mb-1 block text-sm font-bold">Nazwa</label>
                <input type="text" id="qc-name" name="name" required maxlength="255" placeholder="np. Archiwum, Wydarzenia, Z życia fundacji" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <span class="mb-1 block text-sm font-bold">Kolor odznaki <span class="font-normal text-muted">(opcjonalnie)</span></span>
                <input type="hidden" name="color" :value="color">
                <div class="flex items-center gap-2" role="group" aria-label="Kolory z brandbooka">
                    @foreach ($swatches as $hex => $name)
                        <button type="button" title="{{ $name }}" @click="color = (color === '{{ $hex }}' ? '' : '{{ $hex }}')" :aria-pressed="(color === '{{ $hex }}').toString()"
                            class="h-9 w-9 rounded-md border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" style="background: {{ $hex }}"
                            :class="color === '{{ $hex }}' ? 'border-ink' : 'border-white ring-1 ring-gray-300'"><span class="sr-only">{{ $name }}</span></button>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"><i class="fa-solid fa-plus" aria-hidden="true"></i>Dodaj</button>
        </div>
    </form>

    {{-- Lista --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        @forelse ($newsCategories as $category)
            @php $others = $newsCategories->where('id', '!=', $category->id); @endphp
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-gray-100 px-5 py-4 last:border-b-0">
                <span class="h-8 w-8 flex-none rounded-md border border-gray-200" style="background-color: {{ $category->badgeColor() }}" aria-hidden="true"></span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-ink">{{ $category->name }}</p>
                    <p class="text-xs text-muted">
                        <span class="font-mono">/aktualnosci?kategoria={{ $category->slug }}</span> ·
                        <a href="{{ route('admin.newsy.index', ['category' => $category->id]) }}" class="font-bold text-ink underline underline-offset-2 hover:text-brand-dark">{{ $category->news_count }} {{ trans_choice('news|newsy|newsów', $category->news_count) }}</a>
                        @if ($category->news_count !== $category->published_count) · opublikowanych: {{ $category->published_count }} @endif
                    </p>
                </div>
                <div class="flex flex-none items-center gap-1">
                    <a href="{{ route('news.index', ['kategoria' => $category->slug]) }}" target="_blank" rel="noopener" class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Zobacz na stronie" aria-label="Zobacz kategorię {{ $category->name }} na stronie (nowa karta)"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                    <form method="POST" action="{{ route('admin.kategorie-newsow.move', $category) }}">@csrf<input type="hidden" name="direction" value="up">
                        <button type="submit" @disabled($loop->first) class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń {{ $category->name }} wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button></form>
                    <form method="POST" action="{{ route('admin.kategorie-newsow.move', $category) }}">@csrf<input type="hidden" name="direction" value="down">
                        <button type="submit" @disabled($loop->last) class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń {{ $category->name }} niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button></form>
                    <a href="{{ route('admin.kategorie-newsow.edit', $category) }}" class="flex h-10 items-center gap-1.5 rounded-lg px-3 text-sm font-bold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edytuj<span class="sr-only"> kategorię {{ $category->name }}</span></a>
                    <details class="relative">
                        <summary class="flex h-10 cursor-pointer list-none items-center gap-1.5 rounded-lg px-3 text-sm font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 [&::-webkit-details-marker]:hidden"><i class="fa-solid fa-trash" aria-hidden="true"></i>Usuń<span class="sr-only"> kategorię {{ $category->name }}</span></summary>
                        <form method="POST" action="{{ route('admin.kategorie-newsow.destroy', $category) }}" class="absolute right-0 z-20 mt-1 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
                            @csrf @method('DELETE')
                            @if ($category->news_count > 0)
                                <label for="mv-{{ $category->id }}" class="mb-1 block text-sm font-bold">Dokąd przenieść {{ $category->news_count }} {{ trans_choice('news|newsy|newsów', $category->news_count) }}?</label>
                                <select id="mv-{{ $category->id }}" name="move_to" required class="mb-3 w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    <option value="">— wybierz —</option>
                                    <option value="none">Bez kategorii</option>
                                    @foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                                </select>
                            @else
                                <p class="mb-3 text-sm text-ink">Kategoria jest pusta. Usunąć „{{ $category->name }}”?</p>
                            @endif
                            <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-red-700 px-4 text-sm font-bold text-white hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2">Usuń kategorię</button>
                        </form>
                    </details>
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-muted">Brak kategorii. Dodaj pierwszą powyżej.</p>
        @endforelse
    </div>
@endsection
