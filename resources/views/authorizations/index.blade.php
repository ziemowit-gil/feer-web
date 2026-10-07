@extends('layouts.site')

@section('title', 'Rejestr pełnomocnictw i upoważnień — ' . $siteSettings->site_name)
@section('meta_description', 'Publiczny rejestr pełnomocnictw i upoważnień udzielonych przez ' . $siteSettings->site_name . ' — wyszukiwarka i weryfikacja ważności dokumentów.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Rejestr pełnomocnictw i upoważnień', 'url' => null],
    ]])
@endsection

@section('content')
<section class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="mb-2 text-3xl font-bold text-ink">Rejestr pełnomocnictw i upoważnień</h1>
    <p class="mb-8 max-w-3xl text-muted">
        Publiczny wykaz pełnomocnictw i upoważnień udzielonych przez {{ $siteSettings->site_name }}.
        Aby zweryfikować dokument, wpisz jego numer lub nazwisko osoby umocowanej.
        Rejestr prowadzony jest w odrębnym systemie — ta strona prezentuje jego aktualny stan.
    </p>

    {{-- Wyszukiwarka — zwykły formularz GET (działa też bez JS); Alpine tylko
         wysyła formularz automatycznie przy zmianie rodzaju dokumentu. --}}
    <form method="GET" action="{{ route('authorizations.index') }}" x-data
          class="mb-8 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4"
          role="search" aria-label="Wyszukiwarka rejestru">
        <div class="min-w-[16rem] flex-1">
            <label for="rej-q" class="mb-1 block text-sm font-semibold text-ink">Szukaj w rejestrze</label>
            <input id="rej-q" type="search" name="q" value="{{ $q }}"
                   placeholder="Nazwisko, numer dokumentu lub zakres…"
                   class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
        </div>
        <div>
            <label for="rej-typ" class="mb-1 block text-sm font-semibold text-ink">Rodzaj dokumentu</label>
            <select id="rej-typ" name="typ" @change="$el.form.requestSubmit()"
                    class="rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2.5 font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Szukaj
        </button>
        @if ($q !== '' || $type !== '')
            <a href="{{ route('authorizations.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Wyczyść filtry
            </a>
        @endif
    </form>

    {{-- Komunikat o liczbie wyników dla czytników ekranu --}}
    <p class="sr-only" role="status">
        Znaleziono {{ $authorizations->total() }} {{ trans_choice('dokument|dokumenty|dokumentów', $authorizations->total()) }}.
    </p>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <caption class="sr-only">Rejestr pełnomocnictw i upoważnień — wyniki wyszukiwania</caption>
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Nr dokumentu</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Rodzaj</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Udzielający</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Osoba / podmiot umocowany</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Zakres umocowania</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Okres ważności</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($authorizations as $item)
                    @php $status = $item->status(); @endphp
                    <tr class="hover:bg-gray-50 {{ in_array($status, ['expired', 'revoked'], true) ? 'text-muted' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-ink">{{ $item->document_number }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $item->typeLabel() }}</td>
                        <td class="px-4 py-3">{{ $item->principal }}</td>
                        <td class="px-4 py-3 font-semibold text-ink">{{ $item->grantee_name }}</td>
                        <td class="max-w-md px-4 py-3">{{ $item->scope }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $item->valid_from->format('d.m.Y') }}
                            – {{ $item->valid_to?->format('d.m.Y') ?? 'bezterminowo' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($status === 'active')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-800">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Aktywne
                                </span>
                            @elseif ($status === 'upcoming')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800">
                                    <i class="fa-solid fa-clock" aria-hidden="true"></i> Od {{ $item->valid_from->format('d.m.Y') }}
                                </span>
                            @elseif ($status === 'revoked')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">
                                    <i class="fa-solid fa-ban" aria-hidden="true"></i> Unieważnione
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-2.5 py-1 text-xs font-bold text-gray-700">
                                    <i class="fa-solid fa-hourglass-end" aria-hidden="true"></i> Wygasło
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">
                                    <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
                                </span>
                                <p class="text-muted">
                                    @if ($q !== '' || $type !== '')
                                        Brak dokumentów pasujących do podanych kryteriów. Sprawdź pisownię lub
                                        <a href="{{ route('authorizations.index') }}" class="font-bold text-brand hover:text-brand-dark">wyczyść filtry</a>.
                                    @else
                                        Rejestr nie zawiera obecnie żadnych wpisów.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $authorizations->links() }}
    </div>

    <p class="mt-8 text-sm text-muted">
        W razie wątpliwości co do autentyczności dokumentu prosimy o
        <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">kontakt z biurem</a>.
        Stan rejestru na dzień {{ now()->format('d.m.Y') }}.
    </p>
</section>
@endsection
