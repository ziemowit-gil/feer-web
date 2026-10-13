@extends('admin.layout')

@section('title', 'Koordynatorzy')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-ink">Koordynatorzy</h1>
            <p class="mt-1 text-sm text-muted">Osoby kontaktowe działań. Wybierasz je w edycji działania — dane i zdjęcie pokażą się w bloku „Masz pytania? Chętnie odpowiemy”.</p>
        </div>
        <a href="{{ route('admin.koordynatorzy.create') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj koordynatora
        </a>
    </div>

    <div class="rounded-md bg-white">
        @if ($coordinators->isEmpty())
            @include('admin.partials.empty-state', ['icon' => 'fa-user-tie', 'title' => 'Nie ma jeszcze koordynatorów', 'text' => 'Dodaj osoby kontaktowe, a potem wybierz je w działaniach.', 'route' => 'admin.koordynatorzy.create', 'action' => 'Dodaj koordynatora'])
        @else
            <ul class="divide-y divide-gray-100" role="list">
                @foreach ($coordinators as $c)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        @if ($photo = $c->photoUrl())
                            <img src="{{ $photo }}" alt="" class="h-12 w-12 flex-none rounded-md object-cover">
                        @else
                            <span class="flex h-12 w-12 flex-none items-center justify-center rounded-md bg-brand-light text-xl text-brand-dark" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-ink">{{ $c->name }}</p>
                            <p class="text-sm text-ink">{{ collect([$c->email, $c->phone])->filter()->implode(' · ') ?: 'Brak danych kontaktowych' }}</p>
                            <p class="text-xs text-muted">Działania: {{ $c->projects_count }}</p>
                        </div>
                        <a href="{{ route('admin.koordynatorzy.edit', $c) }}" class="inline-flex min-h-9 items-center gap-2 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light">Edytuj<span class="sr-only"> — {{ $c->name }}</span></a>
                        <form method="POST" action="{{ route('admin.koordynatorzy.destroy', $c) }}" onsubmit="return confirm(@js('Usunąć koordynatora „'.$c->name.'”? Działania zostaną bez koordynatora z listy.'))">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex min-h-9 items-center rounded-md px-3 text-sm font-bold text-red-800 hover:bg-red-50">Usuń<span class="sr-only"> — {{ $c->name }}</span></button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
