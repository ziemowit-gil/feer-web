@extends('admin.layout')

@section('title', $author->exists ? 'Edytuj autora' : 'Nowy autor')

@section('content')
    @php $f = 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand'; @endphp
    <form method="POST" action="{{ $author->exists ? route('admin.autorzy-bloga.update', $author) : route('admin.autorzy-bloga.store') }}" enctype="multipart/form-data"
        class="max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($author->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                <input type="text" id="name" name="name" value="{{ old('name', $author->name) }}" required maxlength="120" class="{{ $f }}">
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">Adres (slug) <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $author->slug) }}" maxlength="120" placeholder="z imienia i nazwiska" class="{{ $f }}">
                @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm font-bold">Rola <span class="font-normal text-muted">(np. Trenerka dostępności)</span></label>
            <input type="text" id="role" name="role" value="{{ old('role', $author->role) }}" maxlength="160" class="{{ $f }}">
            @error('role') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="bio" class="mb-1 block text-sm font-bold">Krótka nota</label>
            <textarea id="bio" name="bio" rows="4" maxlength="1500" class="{{ $f }}">{{ old('bio', $author->bio) }}</textarea>
            @error('bio') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        @include('blog::admin.partials.image-field', [
            'prefix' => 'photo', 'legend' => 'Zdjęcie autora (opcjonalnie)',
            'fileName' => 'photo', 'currentUrl' => $author->photoUrl(), 'currentAlt' => $author->photo_alt,
            'removeName' => 'remove_photo', 'altName' => 'photo_alt', 'altValue' => old('photo_alt', $author->photo_alt),
            'altLabel' => 'Tekst alternatywny zdjęcia',
            'altHelp' => 'Puste = imię i nazwisko autora.',
        ])

        <div>
            <label for="links" class="mb-1 block text-sm font-bold">Linki <span class="font-normal text-muted">(po jednym w wierszu)</span></label>
            <textarea id="links" name="links" rows="3" aria-describedby="links-help" class="{{ $f }}">{{ old('links', $author->linksText()) }}</textarea>
            <p id="links-help" class="mt-1 text-xs text-muted">Format: „Etykieta | https://…”, np. „LinkedIn | https://www.linkedin.com/in/…”.</p>
            @error('links') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $author->email) }}" maxlength="255" class="{{ $f }}">
                @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                <label class="mt-2 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_email" value="1" @checked(old('show_email', $author->show_email)) class="rounded border-gray-300 text-brand focus:ring-brand">
                    Pokazuj e-mail publicznie
                </label>
            </div>
            <div>
                <label for="user_id" class="mb-1 block text-sm font-bold">Konto w CMS <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <select id="user_id" name="user_id" aria-describedby="user-help" class="{{ $f }}">
                    <option value="">— brak —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((int) old('user_id', $author->user_id) === $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                <p id="user-help" class="mt-1 text-xs text-muted">Nowe wpisy tej osoby będą miały ją domyślnie jako autora.</p>
                @error('user_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-start gap-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $author->is_active ?? true)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
            <span>
                <span class="block text-sm font-bold">Aktywny</span>
                <span class="block text-xs text-muted">Nieaktywny autor nie pojawia się na liście wyboru w formularzu wpisu.</span>
            </span>
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.autorzy-bloga.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
