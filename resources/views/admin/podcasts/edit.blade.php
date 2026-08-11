@extends('admin.layout')

@section('title', 'Edytuj podcast')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-2 text-sm text-muted" aria-label="Ścieżka nawigacji">
            <a href="{{ route('admin.podcasty.index') }}" class="hover:text-brand">Podcasty</a>
            <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
            <span class="truncate text-ink">{{ $podcast->title }}</span>
        </nav>

        <div class="flex items-center gap-2">
            @if ($podcast->is_published)
                <a href="{{ route('podcasts.show', $podcast) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-muted hover:border-brand hover:text-brand">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Podgląd
                </a>
            @endif
            <form method="POST" action="{{ route('admin.podcasty.destroy', $podcast) }}"
                onsubmit="return confirm('Usunąć podcast „{{ addslashes($podcast->title) }}"?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.podcasty.update', $podcast) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')

        @include('admin.podcasts._form')

        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-6 py-4">
            <button type="submit"
                class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Zapisz zmiany
            </button>
            <a href="{{ route('admin.podcasty.index') }}"
                class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-50">
                Anuluj
            </a>
            <span class="ml-auto text-xs text-muted">
                Ostatnia zmiana: {{ $podcast->updated_at->diffForHumans() }}
            </span>
        </div>
    </form>
@endsection
