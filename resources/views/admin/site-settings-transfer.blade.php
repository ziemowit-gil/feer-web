@extends('admin.layout')

@section('title', 'Przenieś ustawienia')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h2 class="text-xl font-extrabold text-ink">Przenieś ustawienia</h2>
        <p class="mt-2 text-sm text-ink">Kopiuje wybrane ustawienia z jednej witryny do innych. Logo, pozycje menu, dane kontaktowe, hasła i integracje zostają bez zmian.</p>

        <form method="POST" action="{{ route('admin.witryny.ustawienia.copy') }}" class="mt-6 space-y-6">
            @csrf
            <div>
                <label for="source_site_id" class="mb-1 block text-sm font-bold text-ink">Skopiuj z witryny</label>
                <select id="source_site_id" name="source_site_id" required class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected((int) old('source_site_id', $current->id) === $site->id)>{{ $site->site_name ?: $site->slug }}</option>
                    @endforeach
                </select>
                @error('source_site_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="mb-2 text-sm font-bold text-ink">Co przenieść</legend>
                <div class="space-y-2">
                    @foreach ($groups as $key => $group)
                        <label class="flex items-start gap-3 rounded-md bg-gray-100 px-3 py-3 text-sm text-ink">
                            <input type="checkbox" name="groups[]" value="{{ $key }}" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(in_array($key, (array) old('groups', ['header'])))>
                            <span><strong class="block">{{ $group['label'] }}</strong>{{ $group['hint'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('groups') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset>
                <legend class="mb-2 text-sm font-bold text-ink">Do witryn</legend>
                <div class="space-y-2">
                    @foreach ($sites as $site)
                        <label class="flex min-h-11 items-center gap-3 rounded-md bg-gray-100 px-3 text-sm text-ink">
                            <input type="checkbox" name="target_site_ids[]" value="{{ $site->id }}" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(in_array($site->id, (array) old('target_site_ids', [])))>
                            {{ $site->site_name ?: $site->slug }}
                        </label>
                    @endforeach
                </div>
                @error('target_site_ids') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            <div class="flex gap-3">
                <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2" onclick="return confirm('Nadpisać wybrane ustawienia w zaznaczonych witrynach?');">
                    <i class="fa-solid fa-copy" aria-hidden="true"></i> Kopiuj ustawienia
                </button>
                <a href="{{ route('admin.wybor-witryny') }}" class="rounded px-4 py-2 text-sm font-bold text-ink hover:bg-gray-100">Wróć do wyboru witryny</a>
            </div>
        </form>
    </div>
@endsection
