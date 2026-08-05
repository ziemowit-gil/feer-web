@extends('admin.layout')

@section('title', 'Nowe zaproszenie do strefy')

@section('content')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-6 text-lg font-bold text-ink">Nowe zaproszenie do strefy</h1>

        <form method="POST" action="{{ route('admin.zaproszenia-strefy.store') }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-bold text-ink">Adres e-mail <span class="text-red-500" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" required autocomplete="off"
                    value="{{ old('email') }}"
                    placeholder="np. jan.kowalski@partner.org"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-muted">Osoba z tym e-mailem będzie mogła dołączyć do strefy — przez MS365 lub magic link.</p>
            </div>

            <div>
                <label for="note" class="mb-1 block text-sm font-bold text-ink">Notatka (widoczna tylko w adminie)</label>
                <input type="text" id="note" name="note"
                    value="{{ old('note') }}"
                    placeholder="np. Zewnętrzny trener — warsztaty 2026"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand @error('note') border-red-400 @enderror">
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="expires_at" class="mb-1 block text-sm font-bold text-ink">Data wygaśnięcia</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                    value="{{ old('expires_at') }}"
                    class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand @error('expires_at') border-red-400 @enderror">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-muted">Zostaw puste, aby link nie wygasał automatycznie.</p>
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <button type="submit"
                    class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Wygeneruj zaproszenie
                </button>
                <a href="{{ route('admin.zaproszenia-strefy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
