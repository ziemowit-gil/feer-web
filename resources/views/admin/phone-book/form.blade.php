@extends('admin.layout')

@section('title', $entry->exists ? 'Edytuj wpis' : 'Nowa osoba')

@section('content')
    @php $f = 'w-full rounded-lg border-gray-300 focus:border-brand-dark focus:ring-brand-dark'; @endphp
    <form method="POST" action="{{ $entry->exists ? route('admin.ksiazka-telefoniczna.update', $entry) : route('admin.ksiazka-telefoniczna.store') }}" class="max-w-2xl space-y-5 rounded-md bg-white p-6">
        @csrf
        @if ($entry->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                <input type="text" id="name" name="name" value="{{ old('name', $entry->name) }}" required maxlength="120" class="{{ $f }}">
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="role" class="mb-1 block text-sm font-bold">Stanowisko lub rola <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="role" name="role" value="{{ old('role', $entry->role) }}" maxlength="160" placeholder="np. koordynatorka szkoleń" class="{{ $f }}">
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-bold">Numer komórkowy</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $entry->phone) }}" maxlength="40" inputmode="tel" placeholder="np. 601 350 487" class="{{ $f }}">
                @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $entry->email) }}" maxlength="190" class="{{ $f }}">
                @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="topic" class="mb-1 block text-sm font-bold">W jakiej sprawie się kontaktować</label>
            <textarea id="topic" name="topic" rows="2" maxlength="500" placeholder="np. zapisy na szkolenia, faktury, media" class="{{ $f }}">{{ old('topic', $entry->topic) }}</textarea>
            @error('topic') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="hours" class="mb-1 block text-sm font-bold">Kiedy dzwonić <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="hours" name="hours" value="{{ old('hours', $entry->hours) }}" maxlength="160" placeholder="pon.–śr. 10:00–12:00" class="{{ $f }}">
            </div>
            <div>
                <label for="group" class="mb-1 block text-sm font-bold">Dział <span class="font-normal text-muted">(tabela na stronie)</span></label>
                <input type="text" id="group" name="group" value="{{ old('group', $entry->group) }}" maxlength="120" list="phonebook-groups" placeholder="np. Biuro" class="{{ $f }}">
                <datalist id="phonebook-groups">@foreach ($groups as $g)<option value="{{ $g }}">@endforeach</datalist>
            </div>
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $entry->order ?? 0) }}" class="{{ $f }}">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm font-bold">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $entry->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            Pokaż na stronie
        </label>

        <div class="flex gap-3">
            <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.ksiazka-telefoniczna.index') }}" class="rounded px-4 py-2 text-sm font-bold text-ink hover:bg-gray-100">Anuluj</a>
        </div>
    </form>
@endsection
