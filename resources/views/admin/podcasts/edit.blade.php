@extends('admin.layout')

@section('title', 'Edytuj podcast')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-2 text-sm text-muted" aria-label="Ścieżka nawigacji">
            <a href="{{ route('admin.podcasty.index') }}" class="hover:text-brand-dark">Podcasty</a>
            <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
            <span class="truncate text-ink">{{ $podcast->title }}</span>
        </nav>

        <div class="flex items-center gap-2">
            @if ($podcast->is_published)
                <a href="{{ route('podcasts.show', $podcast) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium text-muted hover:text-brand-dark bg-white hover:bg-brand-light">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Podgląd
                </a>
            @endif
            <form method="POST" action="{{ route('admin.podcasty.destroy', $podcast) }}"
                onsubmit="return confirm('Usunąć podcast „{{ addslashes($podcast->title) }}”?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium text-red-800 bg-red-50 hover:bg-red-100">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.podcasty.update', $podcast) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')

        @include('admin.podcasts._form')

        <div class="flex items-center gap-3 px-6 py-4 rounded-md bg-white">
            <button type="submit"
                class="rounded-lg bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Zapisz zmiany
            </button>
            <a href="{{ route('admin.podcasty.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-muted bg-gray-100 hover:bg-brand-light">
                Anuluj
            </a>
            <span class="ml-auto text-xs text-muted">
                Ostatnia zmiana: {{ $podcast->updated_at->diffForHumans() }}
            </span>
        </div>
    </form>
@endsection
