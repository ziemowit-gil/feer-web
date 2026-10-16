@extends('admin.layout')
@section('title', 'Import subskrybentów')
@section('content')
    @include('newsletter::admin.partials.flash')
    <p class="mb-4 max-w-3xl text-sm text-ink">Import przebiega w dwóch krokach: najpierw zobaczysz podgląd (liczby wg statusu, mapowanie kolumn i grup), dopiero potem uruchomisz zapis. Import jest bezpieczny do powtórzenia — rekordy łączymy po adresie e-mail, więc ponowny import aktualizuje dane zamiast tworzyć duplikaty.</p>

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <section aria-labelledby="import-file-heading" class="space-y-4 rounded-md bg-white p-6">
            <h2 id="import-file-heading" class="text-lg font-bold">Z pliku CSV lub JSON</h2>
            <form method="POST" action="{{ route('admin.newsletter.subskrybenci.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="file" class="mb-1 block text-sm font-bold">Plik (max 20 MB)</label>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.json" required aria-describedby="file-help" class="w-full rounded border border-gray-500 text-sm file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-bold">
                    <p id="file-help" class="mt-1 text-xs text-muted">CSV z nagłówkiem (separator „;”, „,” lub tabulator) albo JSON — tablica obiektów. Kolumny dopasujesz w następnym kroku; rozpoznajemy m.in. <code>email</code>, <code>name</code>, <code>last_name</code>, <code>phone</code>, <code>status</code>, <code>subscribed_at</code>, <code>ip_address</code>, <code>groups</code>.</p>
                </div>
                <fieldset>
                    <legend class="mb-1 text-sm font-bold">Skąd pochodzi plik?</legend>
                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="source" value="file" checked class="mt-1 border-gray-500 text-brand-dark focus:ring-brand-dark"> <span>Z innego systemu lub arkusza</span></label>
                    <label class="mt-2 flex items-start gap-2 text-sm"><input type="radio" name="source" value="mailerlite_csv" class="mt-1 border-gray-500 text-brand-dark focus:ring-brand-dark"> <span>Eksport z MailerLite — data zapisu i IP trafią do rejestru zgód ze źródłem „import MailerLite”</span></label>
                </fieldset>
                <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink">Dalej: podgląd</button>
            </form>
        </section>

        <section aria-labelledby="import-ml-heading" class="space-y-4 rounded-md bg-white p-6">
            <h2 id="import-ml-heading" class="text-lg font-bold">Z MailerLite przez API</h2>
            <p class="text-sm text-ink">Pobierzemy wszystkich subskrybentów (aktywnych, niepotwierdzonych, wypisanych, odbitych i oznaczonych jako spam) razem z grupami. Wypisani i odbici trafią na listę tłumienia.</p>
            @if ($tokenMask)
                <p class="text-sm"><strong>Token:</strong> {{ $tokenMask }} {{ $tokenInDb ? '(zapisany w panelu, zaszyfrowany)' : '(z pliku .env — MAILERLITE_API_TOKEN)' }}</p>
            @endif
            <form method="POST" action="{{ route('admin.newsletter.subskrybenci.import.mailerlite') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="token" class="mb-1 block text-sm font-bold">{{ $tokenMask ? 'Nowy token API (opcjonalnie)' : 'Token API MailerLite' }}</label>
                    <input id="token" name="token" type="password" autocomplete="off" spellcheck="false" aria-describedby="token-help" @if(! $tokenMask) required @endif class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark" @error('token') aria-invalid="true" @enderror>
                    <p id="token-help" class="mt-1 text-xs text-muted">MailerLite → Integracje → MailerLite API → Wygeneruj nowy token. Zapisujemy go zaszyfrowanego; puste pole = użyj zapisanego.</p>
                    @error('token')<p class="mt-1 text-sm font-bold text-red-900" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink">Połącz i pokaż podgląd</button>
            </form>
            @if ($tokenInDb)
                <form method="POST" action="{{ route('admin.newsletter.subskrybenci.import.mailerlite') }}">
                    @csrf
                    <input type="hidden" name="forget_token" value="1">
                    <button type="submit" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light">Usuń zapisany token</button>
                </form>
            @endif
        </section>
    </div>

    @if ($imports->isNotEmpty())
        <section aria-labelledby="import-history-heading" class="mt-8 max-w-5xl">
            <h2 id="import-history-heading" class="mb-3 text-lg font-bold">Ostatnie importy</h2>
            <div class="overflow-x-auto rounded-md bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                        <tr><th scope="col" class="px-3 py-3">Data</th><th scope="col" class="px-3 py-3">Źródło</th><th scope="col" class="px-3 py-3">Status</th><th scope="col" class="px-3 py-3">Dodani</th><th scope="col" class="px-3 py-3">Zaktualizowani</th><th scope="col" class="px-3 py-3">Pominięci</th><th scope="col" class="px-3 py-3">Błędy</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($imports as $imp)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2"><a href="{{ route('admin.newsletter.subskrybenci.import.show', $imp) }}" class="font-bold text-brand-dark hover:underline">{{ $imp->created_at->format('Y-m-d H:i') }}</a></td>
                            <td class="px-3 py-2">{{ $imp->sourceLabel() }}@if ($imp->file_name) <span class="text-muted">· {{ $imp->file_name }}</span>@endif</td>
                            <td class="px-3 py-2">{{ $imp->statusLabel() }}</td>
                            <td class="px-3 py-2">{{ $imp->created_count }}</td>
                            <td class="px-3 py-2">{{ $imp->updated_count }}</td>
                            <td class="px-3 py-2">{{ $imp->skipped_count }}</td>
                            <td class="px-3 py-2">{{ $imp->failed_count }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <p class="mt-6"><a href="{{ route('admin.newsletter.subskrybenci.index') }}" class="text-sm font-bold text-brand-dark hover:underline">← Subskrybenci</a></p>
@endsection
