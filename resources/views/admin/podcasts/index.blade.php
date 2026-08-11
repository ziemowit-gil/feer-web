@extends('admin.layout')

@section('title', 'Podcasty')

@section('content')

    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2" role="search">
            <label for="search-podcasts" class="sr-only">Szukaj podcastów</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                <input type="search" id="search-podcasts" name="q" value="{{ request('q') }}"
                    placeholder="Szukaj po tytule…"
                    class="w-64 rounded-lg border border-gray-300 py-1.5 pl-8 pr-3 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
            @if (request('q'))
                <a href="{{ route('admin.podcasty.index') }}" class="text-xs text-muted hover:text-brand">Wyczyść</a>
            @endif
        </form>

        <a href="{{ route('admin.podcasty.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy odcinek
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm" aria-label="Lista podcastów">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="w-14 px-4 py-3"></th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Odcinek</th>
                    <th class="hidden px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted sm:table-cell">Data</th>
                    <th class="hidden px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted md:table-cell">Typ</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-muted">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($podcasts as $podcast)
                    <tr class="group transition-colors hover:bg-gray-50 {{ $podcast->trashed() ? 'opacity-60' : '' }}">

                        {{-- Okładka --}}
                        <td class="px-4 py-3">
                            @if ($podcast->getFirstMediaUrl('cover'))
                                <img src="{{ $podcast->getFirstMediaUrl('cover') }}"
                                    alt=""
                                    class="h-10 w-10 rounded-md object-cover shadow-sm">
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-gray-100">
                                    <i class="fa-solid fa-microphone text-sm text-gray-400" aria-hidden="true"></i>
                                </div>
                            @endif
                        </td>

                        {{-- Tytuł --}}
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-2">
                                @if ($podcast->episode_number)
                                    <span class="mt-0.5 shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-muted">
                                        {{ $podcast->episode_number }}
                                    </span>
                                @endif
                                <div>
                                    <p class="font-medium text-ink">{{ $podcast->title }}</p>
                                    @if ($podcast->getFirstMedia('audio'))
                                        <p class="text-xs text-muted">
                                            <i class="fa-solid fa-headphones mr-1" aria-hidden="true"></i>
                                            {{ number_format($podcast->getFirstMedia('audio')->size / 1048576, 1) }} MB
                                        </p>
                                    @else
                                        <p class="text-xs text-amber-600">
                                            <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Brak pliku audio
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Data --}}
                        <td class="hidden px-4 py-3 text-muted sm:table-cell">
                            {{ $podcast->published_at?->format('d.m.Y') ?? '—' }}
                        </td>

                        {{-- Typ --}}
                        <td class="hidden px-4 py-3 md:table-cell">
                            @if ($podcast->is_premium)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">
                                    <i class="fa-solid fa-star text-[9px]" aria-hidden="true"></i> Premium
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">Bezpłatny</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if ($podcast->trashed())
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Usunięty</span>
                            @elseif ($podcast->is_published)
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Opublikowany
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">Szkic</span>
                            @endif
                        </td>

                        {{-- Akcje --}}
                        <td class="px-4 py-3 text-right">
                            @if ($podcast->trashed())
                                <form method="POST" action="{{ route('admin.podcasty.restore', $podcast->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded border border-gray-300 px-3 py-1 text-xs font-medium text-muted hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('admin.podcasty.edit', $podcast) }}"
                                    class="inline-flex items-center gap-1 rounded border border-gray-300 px-3 py-1 text-xs font-medium text-ink hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-brand">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj
                                </a>
                                <form method="POST" action="{{ route('admin.podcasty.destroy', $podcast) }}" class="ml-1 inline"
                                    onsubmit="return confirm('Usunąć podcast „{{ addslashes($podcast->title) }}"?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                <i class="fa-solid fa-microphone text-2xl text-gray-400" aria-hidden="true"></i>
                            </div>
                            <p class="text-sm font-medium text-muted">
                                @if (request('q'))
                                    Brak wyników dla „{{ request('q') }}"
                                @else
                                    Nie dodano jeszcze żadnych podcastów.
                                @endif
                            </p>
                            @unless (request('q'))
                                <a href="{{ route('admin.podcasty.create') }}"
                                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:text-brand-dark">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pierwszy odcinek
                                </a>
                            @endunless
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($podcasts->hasPages())
        <div class="mt-5">{{ $podcasts->links() }}</div>
    @endif
@endsection
