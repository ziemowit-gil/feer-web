@extends('admin.layout')

@section('title', $user->exists ? 'Edytuj użytkownika' : 'Nowy użytkownik')

@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.uzytkownicy.update', $user) : route('admin.uzytkownicy.store') }}" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm font-bold">Rola</label>
            <select id="role" name="role" required onchange="document.getElementById('group-field').hidden = this.value === 'admin'"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <option value="editor" {{ old('role', $user->role) === 'editor' ? 'selected' : '' }}>Edytor</option>
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator</option>
            </select>
            @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div id="group-field" {{ old('role', $user->role) === 'admin' ? 'hidden' : '' }}>
            <label for="user_group_id" class="mb-1 block text-sm font-bold">Grupa</label>
            <select id="user_group_id" name="user_group_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <option value="">— brak (bez dostępu do modułów) —</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" {{ (int) old('user_group_id', $user->user_group_id) === $group->id ? 'selected' : '' }}>
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-muted">Decyduje, do których modułów panelu edytor ma dostęp. Dotyczy tylko roli Edytor.</p>
            @error('user_group_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-bold">
                {{ $user->exists ? 'Nowe hasło' : 'Hasło' }}
            </label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                placeholder="{{ $user->exists ? 'zostaw puste, aby nie zmieniać' : '' }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-gray-100 pt-5">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="local_login_allowed" value="0">
                <input type="checkbox" name="local_login_allowed" value="1"
                    {{ old('local_login_allowed', $user->local_login_allowed ?? false) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                Dostęp awaryjny (logowanie hasłem gdy MS wyłączone)
            </label>
            <p class="ml-6 mt-1 text-xs text-muted">
                Pozwala temu kontu logować się hasłem nawet gdy opcja „tylko Microsoft 365" jest aktywna. Używaj tylko dla konta zapasowego.
            </p>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.uzytkownicy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>

    @if ($user->exists)
        <div class="mt-6 max-w-xl rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-sm font-bold text-ink">Logowanie Microsoft 365</h2>
            @if ($user->microsoft_id)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="inline-flex items-center gap-2 text-sm text-green-700">
                        <i class="fa-brands fa-microsoft"></i>
                        Konto połączone z Microsoft 365 — użytkownik może logować się przez SSO.
                    </p>
                    <form method="POST" action="{{ route('admin.uzytkownicy.microsoft.unlink', $user) }}"
                        onsubmit="return confirm('Odłączyć konto Microsoft 365 od tego użytkownika?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded border border-amber-300 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50">
                            <i class="fa-solid fa-link-slash"></i> Odłącz konto
                        </button>
                    </form>
                </div>
            @else
                <p class="mt-3 text-sm text-muted">
                    Brak połączenia. Konto zostanie powiązane automatycznie, gdy użytkownik pierwszy raz zaloguje się przez
                    Microsoft 365 tym samym adresem e-mail.
                </p>
            @endif
        </div>
    @endif
@endsection
