@extends('admin.layout')

@section('title', $coordinator->exists ? 'Koordynator: '.$coordinator->name : 'Nowy koordynator')

@section('content')
    <nav class="mb-4 text-sm" aria-label="Ścieżka">
        <a href="{{ route('admin.koordynatorzy.index') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Koordynatorzy</a>
        <span aria-hidden="true" class="mx-1 text-muted">/</span>
        <span aria-current="page">{{ $coordinator->exists ? $coordinator->name : 'Nowy' }}</span>
    </nav>
    <h1 class="mb-6 text-2xl font-bold text-ink">{{ $coordinator->exists ? 'Edytuj koordynatora' : 'Nowy koordynator' }}</h1>

    <form method="POST" action="{{ $coordinator->exists ? route('admin.koordynatorzy.update', $coordinator) : route('admin.koordynatorzy.store') }}" class="max-w-3xl space-y-5 rounded-md bg-white p-6">
        @csrf
        @if ($coordinator->exists) @method('PUT') @endif

        @if ($errors->any())
            <p class="rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">Popraw zaznaczone pola.</p>
        @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="text-red-700" aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" required maxlength="160" value="{{ old('name', $coordinator->name) }}" @error('name') aria-invalid="true" aria-describedby="name-err" @enderror
                class="w-full rounded-md border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('name')<p id="name-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
                <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $coordinator->email) }}" @error('email') aria-invalid="true" aria-describedby="email-err" @enderror
                    class="w-full rounded-md border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @error('email')<p id="email-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-bold">Telefon</label>
                <input id="phone" name="phone" type="tel" maxlength="60" value="{{ old('phone', $coordinator->phone) }}"
                    class="w-full rounded-md border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            </div>
        </div>
        <div>
            <label for="phone_hours" class="mb-1 block text-sm font-bold">Godziny telefonu <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input id="phone_hours" name="phone_hours" type="text" maxlength="160" value="{{ old('phone_hours', $coordinator->phone_hours) }}" placeholder="np. pon.–pt. 9:00–15:00"
                class="w-full rounded-md border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>
        <div>
            <label for="person_page_id" class="mb-1 block text-sm font-bold">Osoba z zespołu <span class="font-normal text-muted">(zdjęcie)</span></label>
            <select id="person_page_id" name="person_page_id" class="w-full rounded-md border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                <option value="">— dobierz automatycznie (po e-mailu albo imieniu i nazwisku) —</option>
                @foreach ($people as $person)
                    <option value="{{ $person->id }}" @selected((int) old('person_page_id', $coordinator->person_page_id) === $person->id)>{{ $person->title }}{{ $person->content_image ? '' : ' (bez zdjęcia)' }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-muted">Zdjęcie bierzemy ze strony osoby w zespole. Bez zdjęcia na stronie działania pokaże się ikona ludzika.</p>
        </div>

        @if ($coordinator->exists && $coordinator->projects->isNotEmpty())
            <div>
                <p class="text-sm font-bold">Działania tego koordynatora</p>
                <ul class="mt-1 space-y-0.5 text-sm" role="list">
                    @foreach ($coordinator->projects as $p)
                        <li><a href="{{ route('admin.projekty.edit', $p) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $p->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" name="stay" value="1" class="rounded-md bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.koordynatorzy.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
