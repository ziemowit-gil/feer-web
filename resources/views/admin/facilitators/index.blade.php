@extends('admin.layout')

@section('title', 'Katalog prowadzących')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Prowadzący/e dodani do katalogu mogą być szybko wstawiani do formularza wydarzenia.</p>
        <a href="{{ route('admin.prowadzacy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj prowadzącego/ą
        </a>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    @if ($facilitators->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white px-6 py-10 text-center text-muted">
            <i class="fa-solid fa-chalkboard-user mb-3 text-3xl text-gray-300" aria-hidden="true"></i>
            <p class="font-bold">Katalog jest pusty.</p>
            <p class="mt-1 text-sm"><a href="{{ route('admin.prowadzacy.create') }}" class="text-brand underline">Dodaj pierwszą osobę prowadzącą</a>, żeby móc szybko wstawiać ją do wydarzeń.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($facilitators as $f)
                <div class="flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-4">
                    @if ($f->photoUrl())
                        <img src="{{ $f->photoUrl() }}" alt="Zdjęcie: {{ $f->name }}"
                            class="h-16 w-16 flex-none rounded-full object-cover ring-1 ring-gray-200">
                    @else
                        <span class="flex h-16 w-16 flex-none items-center justify-center rounded-full bg-gray-100 text-gray-300">
                            <i class="fa-solid fa-user text-2xl" aria-hidden="true"></i>
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-ink">{{ $f->name }}</p>
                        @if ($f->role)
                            <p class="text-sm text-muted">{{ $f->role }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a href="{{ route('admin.prowadzacy.edit', $f) }}"
                                class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-brand hover:bg-brand-light">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj
                            </a>
                            <form method="POST" action="{{ route('admin.prowadzacy.destroy', $f) }}"
                                onsubmit="return confirm('Usunąć {{ $f->name }} z katalogu?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-muted hover:bg-red-50 hover:text-red-600">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
