@extends('admin.layout')

@section('title', 'Książka telefoniczna')

@section('content')
    @php $f = 'w-full rounded-lg border-gray-300 focus:border-brand-dark focus:ring-brand-dark'; @endphp
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-ink">Książka telefoniczna</h1>
            <p class="text-sm text-muted">Kto, numer komórkowy i w jakiej sprawie dzwonić. Strona publiczna: <a href="{{ url('/ksiazka-telefoniczna') }}" class="font-bold text-brand-dark underline">/ksiazka-telefoniczna</a>; do treści strony wstawisz ją skrótem <code class="rounded bg-gray-100 px-1">[ksiazka-telefoniczna]</code>.</p>
        </div>
        <a href="{{ route('admin.ksiazka-telefoniczna.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Dodaj osobę</a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-md bg-brand-light px-4 py-3 text-sm font-medium text-ink" role="status">{{ session('status') }}</div>
    @endif

    @if ($entries->isEmpty())
        <div class="rounded-md bg-white p-10 text-center text-muted">Książka jest pusta. Dodaj pierwszą osobę powyżej.</div>
    @else
        <div class="overflow-hidden rounded-md bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3">Osoba</th>
                        <th class="hidden px-4 py-3 md:table-cell">Dział</th>
                        <th class="px-4 py-3">Numer</th>
                        <th class="hidden px-4 py-3 lg:table-cell">W jakiej sprawie</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($entries as $entry)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.ksiazka-telefoniczna.edit', $entry) }}" class="font-bold text-ink hover:text-brand-dark">{{ $entry->name }}</a>
                                @if ($entry->role)<p class="mt-0.5 text-xs text-muted">{{ $entry->role }}</p>@endif
                            </td>
                            <td class="hidden px-4 py-3 text-muted md:table-cell">{{ $entry->group }}</td>
                            <td class="px-4 py-3">{{ $entry->phone }}</td>
                            <td class="hidden px-4 py-3 text-muted lg:table-cell">{{ \Illuminate\Support\Str::limit((string) $entry->topic, 70) }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-bold {{ $entry->is_active ? 'text-ink' : 'text-muted' }}">{{ $entry->is_active ? 'Widoczny' : 'Ukryty' }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.ksiazka-telefoniczna.edit', $entry) }}" class="font-bold text-brand-dark hover:underline">Edytuj</a>
                                    <form method="POST" action="{{ route('admin.ksiazka-telefoniczna.destroy', $entry) }}" onsubmit="return confirm('Usunąć wpis &quot;{{ $entry->name }}&quot;?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="font-bold text-red-700 hover:underline">Usuń</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.ksiazka-telefoniczna.ustawienia') }}" class="mt-8 max-w-2xl space-y-5 rounded-md bg-white p-6">
        @csrf @method('PUT')
        <h2 class="text-base font-bold text-ink">Strona publiczna</h2>
        <div>
            <label for="phonebook_title" class="mb-1 block text-sm font-bold">Tytuł strony</label>
            <input type="text" id="phonebook_title" name="phonebook_title" maxlength="160" value="{{ old('phonebook_title', $settings->phonebook_title) }}" placeholder="Książka telefoniczna" class="{{ $f }}">
            @error('phonebook_title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="phonebook_lead" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(np. kiedy dzwonić, a kiedy pisać)</span></label>
            <textarea id="phonebook_lead" name="phonebook_lead" rows="2" maxlength="1000" class="{{ $f }}">{{ old('phonebook_lead', $settings->phonebook_lead) }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="phonebook_layout" class="mb-1 block text-sm font-bold">Układ</label>
                <select id="phonebook_layout" name="phonebook_layout" class="{{ $f }}">
                    <option value="table" @selected(old('phonebook_layout', $settings->phonebook_layout ?: 'table') === 'table')>Tabele (każdy dział to tabela)</option>
                    <option value="cards" @selected(old('phonebook_layout', $settings->phonebook_layout) === 'cards')>Karty</option>
                </select>
            </div>
            <div>
                <label for="phonebook_sort" class="mb-1 block text-sm font-bold">Kolejność</label>
                <select id="phonebook_sort" name="phonebook_sort" class="{{ $f }}">
                    <option value="manual" @selected(old('phonebook_sort', $settings->phonebook_sort ?: 'manual') === 'manual')>Wg pola „Kolejność”</option>
                    <option value="alpha" @selected(old('phonebook_sort', $settings->phonebook_sort) === 'alpha')>Alfabetycznie (działy i osoby)</option>
                </select>
            </div>
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="phonebook_hide_numbers" value="1" @checked(old('phonebook_hide_numbers', $settings->phonebook_hide_numbers)) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            <span><span class="font-bold">Ukryj numery za przyciskiem</span>
                <span class="block text-xs text-muted">Numer odsłania się po najechaniu, klawiaturą albo kliknięciu. To tylko ukrycie wizualne: numer nadal jest w kodzie strony, więc nie chroni przed botami.</span></span>
        </label>
        <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz ustawienia</button>
    </form>
@endsection
