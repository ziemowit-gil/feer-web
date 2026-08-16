@extends('admin.layout')

@section('title', 'Edytor pliku .env')

@section('content')
@php
    $sensitive = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN', 'PRIVATE', 'PASS', 'CIPHER'];
    $readonly  = ['APP_KEY'];

    $isSensitive = fn(string $k) => collect($sensitive)->contains(fn($s) => str_contains(strtoupper($k), $s));

    // Parse raw .env lines into structured entries
    $entries = collect($lines)->map(function (string $line) {
        if ($line === '' || trim($line) === '') {
            return ['type' => 'blank'];
        }
        if (str_starts_with(trim($line), '#')) {
            return ['type' => 'comment', 'text' => ltrim($line, '# ')];
        }
        if (preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)/i', $line, $m)) {
            $val = trim($m[2]);
            // Strip surrounding quotes
            if (strlen($val) >= 2
                && (($val[0] === '"' && $val[-1] === '"') || ($val[0] === "'" && $val[-1] === "'"))) {
                $val = substr($val, 1, -1);
            }
            return ['type' => 'var', 'key' => $m[1], 'value' => $val, 'raw' => $line];
        }
        return ['type' => 'other', 'raw' => $line];
    });
@endphp

<div class="max-w-3xl space-y-6" x-data="{}">

    {{-- Nagłówek --}}
    <div class="flex items-start gap-4">
        <div class="flex-1">
            <h1 class="text-xl font-bold text-ink">Edytor pliku <code class="rounded bg-gray-100 px-1.5 font-mono text-base">.env</code></h1>
            <p class="mt-1 text-sm text-muted">Plik konfiguracyjny środowiska aplikacji — ścieżka: <code class="font-mono text-xs">{{ base_path('.env') }}</code></p>
        </div>
        <a href="{{ route('admin.ustawienia.edit') }}"
            class="rounded border border-gray-300 px-3 py-1.5 text-sm text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            ← Ustawienia
        </a>
    </div>

    {{-- Ostrzeżenie --}}
    <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-5">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-xl text-amber-600" aria-hidden="true"></i>
            <div class="space-y-1">
                <p class="font-bold text-amber-900">Edytujesz plik konfiguracyjny środowiska</p>
                <p class="text-sm text-amber-800">
                    Niepoprawne zmiany mogą <strong>natychmiast zepsuć działanie aplikacji</strong>.
                    Zanim zapiszesz, upewnij się że:
                </p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-sm text-amber-800">
                    <li>wiesz, co zmienia dana zmienna,</li>
                    <li>masz kopię zapasową pliku,</li>
                    <li>po zapisie przetestujesz stronę.</li>
                </ul>
                <p class="text-sm text-amber-800">Po zapisaniu zostanie automatycznie wykonane <code class="font-mono text-xs">config:clear</code> i <code class="font-mono text-xs">cache:clear</code>.</p>
            </div>
        </div>
    </div>

    {{-- Status / błędy --}}
    @if (session('status'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <i class="fa-solid fa-circle-check mr-2" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fa-solid fa-circle-exclamation mr-2" aria-hidden="true"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Formularz --}}
    <form method="POST" action="{{ route('admin.ustawienia.env.update') }}"
        class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
        @csrf

        @foreach ($entries as $entry)
            @if ($entry['type'] === 'blank')
                {{-- spacer --}}
            @elseif ($entry['type'] === 'comment')
                <div class="bg-gray-50 px-5 py-2">
                    <p class="text-[0.7rem] font-bold uppercase tracking-wide text-muted">{{ $entry['text'] ?: '───' }}</p>
                </div>
            @elseif ($entry['type'] === 'var')
                @php
                    $k = $entry['key'];
                    $v = $entry['value'];
                    $sens = $isSensitive($k);
                    $ro   = in_array($k, $readonly);
                @endphp
                <div class="flex items-start gap-3 px-5 py-3" x-data="{{ $sens ? '{ show: false }' : '{}' }}">
                    <label for="env-{{ $k }}"
                        class="w-56 shrink-0 pt-2 font-mono text-xs font-bold text-ink break-all">{{ $k }}</label>
                    <div class="flex-1 space-y-1">
                        @if ($ro)
                            <div class="flex items-center gap-2">
                                <input id="env-{{ $k }}" type="password"
                                    value="{{ $v }}" disabled
                                    class="w-full rounded border border-gray-200 bg-gray-100 px-3 py-1.5 font-mono text-xs text-muted cursor-not-allowed">
                                <span class="shrink-0 rounded bg-amber-100 px-2 py-1 text-[0.65rem] font-bold text-amber-700">TYLKO ODCZYT</span>
                            </div>
                            <p class="text-[0.7rem] text-muted">Klucz szyfrowania — zmiana spowoduje utratę wszystkich zaszyfrowanych danych.</p>
                        @elseif ($sens)
                            <div class="flex items-center gap-2">
                                <input id="env-{{ $k }}" name="env[{{ $k }}]"
                                    :type="show ? 'text' : 'password'"
                                    value="{{ $v }}"
                                    class="w-full rounded border-gray-300 px-3 py-1.5 font-mono text-xs focus:border-brand focus:ring-brand">
                                <button type="button" @click="show = !show"
                                    :aria-label="show ? 'Ukryj wartość' : 'Pokaż wartość'"
                                    class="shrink-0 rounded border border-gray-300 px-2.5 py-1.5 text-xs text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" aria-hidden="true"></i>
                                </button>
                            </div>
                        @else
                            <input id="env-{{ $k }}" name="env[{{ $k }}]"
                                type="text"
                                value="{{ $v }}"
                                class="w-full rounded border-gray-300 px-3 py-1.5 font-mono text-xs focus:border-brand focus:ring-brand">
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Zapis --}}
        <div class="flex items-center gap-4 px-5 py-4 bg-gray-50 rounded-b-xl">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                onclick="return confirm('Zapisać zmiany w pliku .env? Aplikacja zostanie natychmiast skonfigurowana z nowymi wartościami.')">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Zapisz plik .env
            </button>
            <p class="text-xs text-muted">Po zapisaniu: <code class="font-mono">config:clear</code> + <code class="font-mono">cache:clear</code></p>
        </div>
    </form>

</div>
@endsection
