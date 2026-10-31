@extends('admin.layout')

@section('title', $course->exists ? 'Edytuj szkolenie' : 'Nowe szkolenie')

@php
    $in = 'mt-1 block w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
    $lbl = 'block text-sm font-bold text-ink';
    $btn = 'rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $edFields = function ($e = null) use ($in, $lbl) {
        $v = fn ($k, $d = '') => old($k, $e?->{$k} ?? $d);
        return [$v, $in, $lbl];
    };
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 max-w-3xl rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-bold">Popraw poniższe pola:</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $course->exists ? route('admin.kursy.update', $course) : route('admin.kursy.store') }}" class="max-w-3xl space-y-5 rounded-md bg-white p-6">
        @csrf
        @if ($course->exists) @method('PUT') @endif

        <div><label for="title" class="{{ $lbl }}">Tytuł *</label><input id="title" name="title" required value="{{ old('title', $course->title) }}" class="{{ $in }}"></div>
        <div><label for="slug" class="{{ $lbl }}">Adres (slug)</label><input id="slug" name="slug" value="{{ old('slug', $course->slug) }}" class="{{ $in }}" aria-describedby="slug-h"><p id="slug-h" class="mt-1 text-xs text-muted">Zostaw puste, żeby powstał z tytułu.</p></div>
        <div><label for="lead" class="{{ $lbl }}">Krótki opis</label><textarea id="lead" name="lead" rows="2" maxlength="500" class="{{ $in }}">{{ old('lead', $course->lead) }}</textarea></div>
        <div><label for="description" class="{{ $lbl }}">Pełny opis (HTML)</label><textarea id="description" name="description" rows="10" class="{{ $in }} font-mono">{{ old('description', $course->description) }}</textarea></div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="category" class="{{ $lbl }}">Kategoria *</label>
                <select id="category" name="category" class="{{ $in }}">@foreach (\App\Models\Course::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected(old('category', $course->category) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="format" class="{{ $lbl }}">Forma *</label>
                <select id="format" name="format" class="{{ $in }}">@foreach (\App\Models\Course::FORMATS as $k => $l)<option value="{{ $k }}" @selected(old('format', $course->format) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="price" class="{{ $lbl }}">Cena (zł, puste = bezpłatnie)</label><input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $course->price) }}" class="{{ $in }}"></div>
            <div><label for="status" class="{{ $lbl }}">Status *</label>
                <select id="status" name="status" class="{{ $in }}">@foreach (\App\Models\Course::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $course->status) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="order" class="{{ $lbl }}">Kolejność</label><input id="order" name="order" type="number" min="0" value="{{ old('order', $course->order ?? 0) }}" class="{{ $in }}"></div>
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm font-bold text-ink"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course->is_featured)) class="h-5 w-5 rounded border-gray-500"> Wyróżnij na liście</label></div>
        </div>

        <div class="flex gap-3"><button type="submit" class="{{ $btn }}">Zapisz</button><a href="{{ route('admin.kursy.index') }}" class="rounded border border-gray-500 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-100">Wróć do listy</a></div>
    </form>

    @if ($course->exists)
        <section class="mt-10 max-w-3xl" aria-labelledby="ed-h">
            <h2 id="ed-h" class="text-xl font-bold text-ink">Terminy</h2>

            <div class="mt-4 space-y-3">
                @forelse ($course->editions as $e)
                    <details class="rounded-md bg-white p-4">
                        <summary class="cursor-pointer text-sm font-bold text-ink">
                            {{ $e->datesLabel() }} · zajęte {{ $e->seats_taken }}/{{ $e->capacity }}@if ($e->waitlist_count) · rezerwowa: {{ $e->waitlist_count }}@endif · {{ $e->enrollmentLabel() }}
                        </summary>
                        <p class="mt-3"><a href="{{ route('admin.kursy.registrations.index', $e) }}" class="text-sm font-bold text-brand-dark underline">Uczestnicy i zapisy →</a></p>
                        <form method="POST" action="{{ route('admin.kursy.editions.update', $e) }}" class="mt-3">
                            @csrf @method('PUT')
                            @include('admin.courses.partials.edition-fields', ['e' => $e, 'in' => $in, 'lbl' => $lbl, 'uid' => 'e'.$e->id])
                            <button type="submit" class="mt-3 {{ $btn }}">Zapisz termin</button>
                        </form>
                        <form method="POST" action="{{ route('admin.kursy.editions.destroy', $e) }}" class="mt-3" onsubmit="return confirm('Usunąć termin razem z zapisami?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-bold text-red-700 underline">Usuń termin</button>
                        </form>
                    </details>
                @empty
                    <p class="rounded-md bg-gray-50 p-4 text-sm text-ink">Brak terminów. Dodaj pierwszy poniżej.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.kursy.editions.store', $course) }}" class="mt-6 rounded-md bg-white p-5">
                @csrf
                <h3 class="text-base font-bold text-ink">Dodaj termin</h3>
                @include('admin.courses.partials.edition-fields', ['e' => null, 'in' => $in, 'lbl' => $lbl, 'uid' => 'new'])
                <button type="submit" class="mt-3 {{ $btn }}">Dodaj termin</button>
            </form>
        </section>
    @endif
@endsection
