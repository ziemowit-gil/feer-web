<!DOCTYPE html>
<html lang="pl" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalator weCMS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                brand: { DEFAULT: '#1d4ed8', dark: '#1e40af', light: '#dbeafe' }
            }
        }
    }
}
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
[x-cloak]{display:none}
.step-line::before{content:'';position:absolute;left:15px;top:28px;bottom:-12px;width:2px;background:rgba(255,255,255,.2);z-index:0}
.step-enter{animation:fadeSlide .35s ease both}
@keyframes fadeSlide{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body class="h-full bg-slate-100 font-sans antialiased">

<div class="flex min-h-screen">

    {{-- ── Sidebar ────────────────────────────────────────────────── --}}
    <aside class="hidden w-64 flex-none bg-brand md:flex flex-col py-10 px-6 select-none">
        <div class="mb-10">
            <div class="flex items-center gap-2 text-white">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-sm font-black">W</span>
                <span class="text-lg font-extrabold tracking-tight">weCMS</span>
                <span class="ml-auto rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">
                    v{{ config('app.version', '1.0') }}
                </span>
            </div>
            <p class="mt-2 text-xs text-white/50">Instalator systemu</p>
        </div>

        <nav class="flex flex-col gap-1" aria-label="Kroki instalacji">
        @foreach ($steps as $key => $meta)
            @php
                $idx     = array_search($key, $stepKeys);
                $active  = $key === $step;
                $done    = $idx < $currentIdx;
            @endphp
            <div class="relative {{ !$loop->last ? 'step-line' : '' }} pb-3">
                <div class="relative z-10 flex items-center gap-3 rounded-lg px-2 py-2
                    {{ $active ? 'bg-white/15' : '' }}">
                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-xs
                        {{ $done  ? 'bg-green-400 text-white' : ($active ? 'bg-white text-brand' : 'bg-white/15 text-white/50') }}">
                        @if ($done)
                            <i class="fa-solid fa-check text-[10px]"></i>
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </span>
                    <span class="text-sm font-semibold
                        {{ $active ? 'text-white' : ($done ? 'text-white/80' : 'text-white/40') }}">
                        {{ $meta['title'] }}
                    </span>
                </div>
            </div>
        @endforeach
        </nav>

        <div class="mt-auto text-xs text-white/30">
            &copy; {{ date('Y') }} FEER &middot; weCMS
        </div>
    </aside>

    {{-- ── Content ────────────────────────────────────────────────── --}}
    <main class="flex flex-1 items-start justify-center p-6 md:p-12">
        <div class="w-full max-w-2xl step-enter">

            {{-- Mobile step indicator --}}
            <div class="mb-6 flex items-center gap-2 md:hidden">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                    {{ $currentIdx + 1 }}
                </span>
                <span class="text-sm font-semibold text-gray-600">
                    Krok {{ $currentIdx + 1 }} z {{ count($steps) }} &mdash; {{ $steps[$step]['title'] }}
                </span>
            </div>

            {{-- Error display --}}
            @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <i class="fa-solid fa-circle-exclamation mr-1.5"></i>
                {{ $errors->first() }}
            </div>
            @endif

            {{-- ════ STEP: welcome ════ --}}
            @if ($step === 'welcome')
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-brand text-4xl font-black text-white shadow-lg">W</div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Witamy w weCMS</h1>
                    <p class="mt-2 text-gray-500">System zarządzania treścią dla fundacji i NGO.</p>
                    <span class="mt-3 inline-block rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-brand">
                        Wersja {{ config('app.version', '1.0') }}
                    </span>
                </div>

                <div class="mb-8 grid grid-cols-3 gap-4">
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <i class="fa-solid fa-bolt text-2xl text-brand mb-2"></i>
                        <p class="text-xs font-semibold text-gray-700">Szybka instalacja</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <i class="fa-solid fa-layer-group text-2xl text-brand mb-2"></i>
                        <p class="text-xs font-semibold text-gray-700">Szablony stron</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <i class="fa-solid fa-universal-access text-2xl text-brand mb-2"></i>
                        <p class="text-xs font-semibold text-gray-700">Zgodność WCAG</p>
                    </div>
                </div>

                <p class="mb-6 text-sm leading-relaxed text-gray-500 text-center">
                    Kreator przeprowadzi Cię przez konfigurację bazy danych, konta administratora i podstawowych ustawień serwisu.
                </p>

                <form method="POST" action="{{ route('install.post') }}">
                    @csrf
                    <input type="hidden" name="_step" value="welcome">
                    <button type="submit"
                        class="w-full rounded-xl bg-brand py-3 text-sm font-extrabold text-white shadow transition hover:bg-brand-dark">
                        Zacznij instalację
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>

            {{-- ════ STEP: requirements ════ --}}
            @elseif ($step === 'requirements')
            @php $allPass = collect($requirements)->every(fn($r) => $r['pass']); @endphp
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                <h1 class="mb-1 text-2xl font-extrabold text-gray-900">Wymagania systemowe</h1>
                <p class="mb-6 text-sm text-gray-500">Sprawdzamy czy serwer spełnia minimalne wymagania weCMS.</p>

                <div class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden mb-6">
                    @foreach ($requirements as $req)
                    <div class="flex items-center justify-between px-4 py-3
                        {{ $req['pass'] ? 'bg-white' : 'bg-red-50' }}">
                        <div class="flex items-center gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs
                                {{ $req['pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                <i class="fa-solid {{ $req['pass'] ? 'fa-check' : 'fa-xmark' }}"></i>
                            </span>
                            <span class="text-sm font-medium text-gray-700">{{ $req['label'] }}</span>
                        </div>
                        @if ($req['detail'])
                            <span class="text-xs text-gray-400 font-mono">{{ $req['detail'] }}</span>
                        @elseif (!$req['pass'])
                            <span class="text-xs font-semibold text-red-600">Brak</span>
                        @endif
                    </div>
                    @endforeach
                </div>

                @if (!$allPass)
                <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                    Napraw oznaczone błędy i odśwież stronę.
                </div>
                @endif

                <form method="POST" action="{{ route('install.post') }}" class="flex gap-3">
                    @csrf
                    <input type="hidden" name="_step" value="requirements">
                    <a href="{{ route('install.index') }}"
                        class="flex-none rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-600 transition hover:border-gray-400">
                        <i class="fa-solid fa-rotate-right mr-1"></i> Odśwież
                    </a>
                    <button type="submit" {{ !$allPass ? 'disabled' : '' }}
                        class="flex-1 rounded-xl py-3 text-sm font-extrabold text-white shadow transition
                            {{ $allPass ? 'bg-brand hover:bg-brand-dark' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                        Kontynuuj
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>

            {{-- ════ STEP: configure ════ --}}
            @elseif ($step === 'configure')
            <form method="POST" action="{{ route('install.post') }}" x-data="installer()" class="space-y-4">
                @csrf
                <input type="hidden" name="_step" value="configure">

                {{-- Baner trybu demo --}}
            @if ($demoMode)
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 flex items-start gap-3">
                <i class="fa-solid fa-flask-vial mt-0.5 text-amber-500 text-lg flex-none"></i>
                <div>
                    <p class="text-sm font-bold text-amber-800">Tryb demonstracyjny</p>
                    <p class="text-xs text-amber-700 mt-0.5">Po instalacji zostaną automatycznie załadowane przykładowe dane (aktualności, projekty, hero slajdy itp.).</p>
                </div>
            </div>
            @endif

            {{-- Klucz licencyjny --}}
            @php $devMode = \App\Support\LicenseValidator::isDevMode(); @endphp
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 {{ $devMode ? 'opacity-60' : '' }}">
                <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                    <i class="fa-solid fa-key text-brand"></i>
                    Klucz licencyjny
                    @if ($devMode)
                        <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Pominięty — tryb dev</span>
                    @endif
                </h2>
                <p class="mb-4 text-xs text-gray-500">
                    Klucz otrzymujesz od wydawcy weCMS (FEER). W środowisku developerskim (brak LICENSE_PUBLIC_KEY) pole jest opcjonalne.
                </p>
                <input type="text" name="license_key" value="{{ old('license_key') }}"
                    placeholder="WCMS-XXXXXXXX-XXXXXXXX-XXXXXXXX-..."
                    {{ $devMode ? 'disabled' : 'required' }}
                    class="w-full rounded-lg border px-3 py-2.5 font-mono text-sm tracking-widest focus:outline-none focus:ring-1 focus:ring-brand
                        {{ $errors->has('license_key') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                @error('license_key')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Baza danych --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                        <i class="fa-solid fa-database text-brand"></i>
                        Baza danych
                    </h2>

                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition"
                            :class="db === 'sqlite' ? 'border-brand bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="db_type" value="sqlite" x-model="db" class="accent-brand">
                            <span>
                                <span class="block text-sm font-bold text-gray-900">SQLite</span>
                                <span class="text-xs text-gray-500">Zalecana — plik lokalny, zero konfiguracji</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition"
                            :class="db === 'mysql' ? 'border-brand bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="db_type" value="mysql" x-model="db" class="accent-brand">
                            <span>
                                <span class="block text-sm font-bold text-gray-900">MySQL / MariaDB</span>
                                <span class="text-xs text-gray-500">Dla środowisk produkcyjnych</span>
                            </span>
                        </label>
                    </div>

                    <div x-show="db === 'mysql'" x-cloak class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Host</label>
                            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Port</label>
                            <input type="number" name="db_port" value="{{ old('db_port', '3306') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Nazwa bazy</label>
                            <input type="text" name="db_name" value="{{ old('db_name', 'wecms') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Użytkownik</label>
                            <input type="text" name="db_user" value="{{ old('db_user') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Hasło</label>
                            <input type="password" name="db_pass"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                    </div>
                </div>

                {{-- Konto administratora --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                        <i class="fa-solid fa-user-shield text-brand"></i>
                        Konto administratora
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Imię i nazwisko</label>
                            <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                                placeholder="Jan Kowalski"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Adres e-mail</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                                placeholder="admin@twojafundacja.pl"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Hasło</label>
                            <input type="password" name="admin_password" required minlength="8"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Potwierdź hasło</label>
                            <input type="password" name="admin_password_confirmation" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                    </div>
                </div>

                {{-- Ustawienia serwisu --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                        <i class="fa-solid fa-globe text-brand"></i>
                        Ustawienia serwisu
                    </h2>
                    <div class="grid gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Nazwa serwisu</label>
                            <input type="text" name="site_name" value="{{ old('site_name') }}" required
                                placeholder="Fundacja XYZ"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Tagline / misja <span class="font-normal text-gray-400">(opcjonalnie)</span></label>
                            <input type="text" name="site_tagline" value="{{ old('site_tagline') }}"
                                placeholder="Razem tworzymy dostępny świat"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>

                        {{-- Template picker --}}
                        <div>
                            <label class="mb-2 block text-xs font-semibold text-gray-600">Szablon strony</label>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach (\App\Models\SiteSetting::SITE_TEMPLATES as $tKey => $tLabel)
                                @php
                                    $icons = [
                                        'default' => 'fa-building-columns',
                                        'ngo' => 'fa-hands-holding-heart',
                                        'ngo_mix' => 'fa-hands-holding-heart',
                                        'municipality' => 'fa-city',
                                        'federation' => 'fa-people-group',
                                    ];
                                    $descs = [
                                        'default' => 'Klasyczny układ dla fundacji',
                                        'ngo' => 'Rozbudowany: misja, wsparcie, projekty',
                                        'ngo_mix' => 'Klasyczna belka i stopka, rozbudowana strona główna',
                                        'municipality' => 'Gmina: pogoda, imieniny, BIP',
                                        'federation' => 'Federacja organizacji: wielobarwna, nowoczesna',
                                    ];
                                @endphp
                                <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 text-center transition"
                                    :class="tpl === '{{ $tKey }}' ? 'border-brand bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" name="site_template" value="{{ $tKey }}"
                                        x-model="tpl" class="sr-only"
                                        {{ old('site_template', 'default') === $tKey ? 'checked' : '' }}>
                                    <i class="fa-solid {{ $icons[$tKey] }} text-2xl" :class="tpl === '{{ $tKey }}' ? 'text-brand' : 'text-gray-400'"></i>
                                    <span class="text-xs font-bold" :class="tpl === '{{ $tKey }}' ? 'text-brand' : 'text-gray-700'">
                                        {{ explode(' ', $tLabel)[0] }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 leading-tight">{{ $descs[$tKey] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Moduły --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                        <i class="fa-solid fa-puzzle-piece text-brand"></i>
                        Moduły
                    </h2>
                    <p class="mb-4 text-xs text-gray-500">Wybierz, które funkcje serwisu mają być włączone. Możesz to zmienić później w panelu.</p>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @php $disabledByDefault = ['strategy']; @endphp
                        @foreach (\App\Models\SiteSetting::MODULES as $mKey => $mLabel)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2.5 transition hover:border-gray-300">
                                <input type="checkbox" name="modules[]" value="{{ $mKey }}"
                                    {{ old('modules') ? (in_array($mKey, old('modules', [])) ? 'checked' : '') : (in_array($mKey, $disabledByDefault) ? '' : 'checked') }}
                                    class="h-4 w-4 flex-none rounded accent-brand">
                                <span class="text-sm font-medium text-gray-700">{{ $mLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Ograniczenia wyboru (nagłówek, typy podstron, kontakt) --}}
                @php
                    $blockableGroupMeta = [
                        'header_layouts' => ['icon' => 'fa-heading', 'title' => 'Dozwolone układy nagłówka', 'hint' => 'których administratorzy tej instalacji nie powinni móc wybrać w Ustawieniach → Nagłówek'],
                        'page_types' => ['icon' => 'fa-file-lines', 'title' => 'Dozwolone typy podstron', 'hint' => 'których redaktorzy tej instalacji nie powinni móc wybrać przy tworzeniu podstrony'],
                        'contact_layouts' => ['icon' => 'fa-address-card', 'title' => 'Dozwolone warianty strony kontaktowej', 'hint' => 'których administratorzy tej instalacji nie powinni móc wybrać w Ustawieniach → Kontakt'],
                    ];
                @endphp
                @foreach (\App\Models\SiteSetting::blockableOptionGroups() as $groupKey => $groupOptions)
                    @php $meta = $blockableGroupMeta[$groupKey]; @endphp
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                            <i class="fa-solid {{ $meta['icon'] }} text-brand"></i>
                            {{ $meta['title'] }}
                        </h2>
                        <p class="mb-4 text-xs text-gray-500">
                            Odznacz opcje, {{ $meta['hint'] }}. Domyślnie dostępne są wszystkie.
                        </p>
                        <div class="grid gap-2.5 sm:grid-cols-2">
                            @foreach ($groupOptions as $optKey => $optLabel)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2.5 transition hover:border-gray-300">
                                    <input type="checkbox" name="allowed_{{ $groupKey }}[]" value="{{ $optKey }}"
                                        {{ old("allowed_{$groupKey}") ? (in_array($optKey, old("allowed_{$groupKey}", [])) ? 'checked' : '') : 'checked' }}
                                        class="h-4 w-4 flex-none rounded accent-brand">
                                    <span class="text-sm font-medium text-gray-700">{{ $optLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Certyfikat super-admina --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold text-gray-900">
                        <i class="fa-solid fa-shield-halved text-brand"></i>
                        Certyfikat głównego administratora
                    </h2>
                    <p class="mb-4 text-xs text-gray-500">
                        Kreator wygeneruje certyfikat klienta (.pfx) do logowania pod adresem <code class="rounded bg-gray-100 px-1 py-0.5">/super</code> —
                        niezależnego od zwykłego logowania hasłem. Certyfikat będzie można pobrać tylko raz, zaraz po instalacji.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Hasło certyfikatu</label>
                            <input type="password" name="super_admin_cert_password" required minlength="8"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Potwierdź hasło</label>
                            <input type="password" name="super_admin_cert_password_confirmation" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                    </div>
                    @error('super_admin_cert_password')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dane demo --}}
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="seed_demo" value="1"
                            {{ $demoMode || old('seed_demo') ? 'checked' : '' }}
                            class="mt-0.5 h-4 w-4 flex-none rounded accent-brand">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">
                                <i class="fa-solid fa-seedling text-brand mr-1"></i>
                                Załaduj przykładowe dane demonstracyjne
                            </span>
                            <span class="text-xs text-gray-500">Aktualności, projekty, slajdy, partnerzy — do szybkiego podglądu systemu.</span>
                        </span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-brand py-3.5 text-sm font-extrabold text-white shadow transition hover:bg-brand-dark">
                    <i class="fa-solid fa-rocket mr-2"></i>
                    Zainstaluj weCMS
                </button>
            </form>

            {{-- ════ STEP: done ════ --}}
            @elseif ($step === 'done')
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                    <i class="fa-solid fa-circle-check text-4xl text-green-500"></i>
                </div>
                <h1 class="mb-2 text-3xl font-extrabold text-gray-900">Instalacja zakończona!</h1>
                <p class="mb-8 text-gray-500">weCMS jest gotowy do działania. Zaloguj się do panelu i zacznij budować serwis.</p>

                <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center justify-center gap-2 rounded-xl bg-brand px-8 py-3 text-sm font-extrabold text-white shadow transition hover:bg-brand-dark">
                        <i class="fa-solid fa-gauge-high"></i>
                        Panel administracyjny
                    </a>
                    <a href="{{ route('home') }}"
                        class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-8 py-3 text-sm font-semibold text-gray-700 transition hover:border-brand hover:text-brand">
                        <i class="fa-solid fa-house"></i>
                        Strona główna
                    </a>
                </div>

                {{-- Certyfikat super-admina — dostępny do pobrania tylko raz --}}
                @if (session('install_pfx'))
                <div class="mb-8 rounded-xl border-2 border-amber-300 bg-amber-50 p-5 text-left">
                    <p class="mb-1 flex items-center gap-2 text-sm font-bold text-amber-900">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Pobierz certyfikat super-admina — teraz albo nigdy
                    </p>
                    <p class="mb-4 text-xs text-amber-800">
                        Ten plik .pfx (chroniony hasłem, które podałeś/aś w kreatorze) służy do logowania pod
                        <code class="rounded bg-white px-1 py-0.5">/super</code>. Nie jest zapisywany na serwerze —
                        po pobraniu (lub opuszczeniu tej strony) zniknie bezpowrotnie.
                    </p>
                    <a href="{{ route('install.certificate') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow transition hover:bg-amber-600">
                        <i class="fa-solid fa-download"></i>
                        Pobierz certyfikat (.pfx)
                    </a>
                </div>
                @endif

                {{-- Demo data --}}
                @if ($demoSeeded || session('demo_done'))
                <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-center gap-2">
                    <i class="fa-solid fa-seedling text-green-600"></i>
                    Przykładowe dane zostały załadowane pomyślnie.
                </div>
                @else
                <div class="rounded-xl border border-dashed border-gray-200 p-5">
                    <p class="mb-3 text-sm text-gray-500">
                        <i class="fa-solid fa-seedling text-brand mr-1"></i>
                        Chcesz zobaczyć jak wygląda wypełniony serwis?
                    </p>
                    <form method="POST" action="{{ route('install.post') }}">
                        @csrf
                        <input type="hidden" name="_step" value="demo">
                        <button type="submit"
                            class="rounded-lg border border-brand bg-blue-50 px-5 py-2 text-xs font-bold text-brand transition hover:bg-brand hover:text-white">
                            Dodaj przykładowe dane
                        </button>
                    </form>
                    @if ($errors->has('demo'))
                    <p class="mt-2 text-xs text-red-600">{{ $errors->first('demo') }}</p>
                    @endif
                </div>
                @endif
            </div>

            @endif

        </div>
    </main>
</div>

<script>
function installer() {
    return {
        db:  '{{ old('db_type', 'sqlite') }}',
        tpl: '{{ old('site_template', 'default') }}',
    }
}
</script>
</body>
</html>
