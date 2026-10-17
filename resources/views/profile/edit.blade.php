@extends('admin.layout')

@section('title', 'Mój profil')

{{--
    Mój profil: karta z danymi konta, po lewej spis sekcji (przyklejony na szerokich ekranach), po prawej sekcje w kartach.
    Kotwice bez zmian (#dwuetapowe, #klucze-dostepu, #domyslny-edytor, #wyglad-panelu, #powiadomienia) — linki z innych ekranów działają.
    Kontrast: ink na bieli, linki brand-dark, czerwony #991b1b dla usunięcia konta; cele ≥ 44 px.
--}}
@section('content')
    @php
        $user = auth()->user();
        $isMicrosoft = (bool) $user->microsoft_id;
        $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
        // [kotwica, tytuł, opis, widok, grupa, czy widoczna]
        $sections = array_values(array_filter([
            ['dane-profilu', 'Dane profilu', 'Imię wyświetlane w panelu i adres e-mail do logowania.', null, 'Konto', true],
            ['haslo', 'Hasło', 'Używaj długiego, losowego hasła.', 'profile.partials.update-password-form', 'Konto', ! $isMicrosoft],
            ['dwuetapowe', 'Logowanie dwuetapowe', 'Dodatkowe zabezpieczenie przy logowaniu hasłem.', 'profile.partials.two-factor-form', 'Bezpieczeństwo', ! $isMicrosoft],
            ['klucze-dostepu', 'Klucze dostępu', 'Logowanie bez hasła: YubiKey, odcisk palca, Windows Hello albo telefon.', 'profile.partials.passkeys-form', 'Bezpieczeństwo', $siteSettings->passkeyLoginEnabled() && Route::has('passkeys.store')],
            ['domyslny-edytor', 'Domyślny edytor', 'Którym edytorem otwierać strony, aktualności i działania.', 'profile.partials.default-editor-form', 'Praca w panelu', \App\Models\SiteSetting::current()->isModuleEnabled('block_editor')],
            ['wyglad-panelu', 'Wygląd panelu', 'Kolory tła i pasków w panelu administracyjnym.', 'profile.partials.admin-scheme-form', 'Praca w panelu', Route::has('profile.scheme')],
            ['powiadomienia', 'Powiadomienia', 'O czym chcesz dostawać e-maile.', 'profile.partials.notification-preferences-form', 'Praca w panelu', true],
            ['usun-konto', 'Usuń konto', 'Nieodwracalne usunięcie konta i wszystkich danych.', 'profile.partials.delete-user-form', 'Usunięcie konta', ! $isMicrosoft],
        ], fn ($s) => $s[5]));
        $groups = collect($sections)->groupBy(fn ($s) => $s[4]);
        $navLink = 'flex min-h-11 items-center rounded-md px-3 text-sm font-bold text-ink hover:bg-brand-light hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <div class="mx-auto max-w-6xl">
        {{-- Karta konta --}}
        <header class="mb-6 flex flex-wrap items-center gap-5 rounded-lg bg-white p-6">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="h-16 w-16 flex-none rounded-full object-cover">
            @else
                <span class="inline-flex h-16 w-16 flex-none items-center justify-center rounded-full bg-brand-dark text-xl font-bold text-white" aria-hidden="true">{{ $initials }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-ink">Mój profil</h1>
                <p class="mt-0.5 text-base font-bold text-ink">{{ $user->name ?: '—' }}</p>
                <p class="text-sm text-ink">{{ $user->email }}</p>
            </div>
            <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Rola</dt><dd class="font-bold text-ink">{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</dd></div>
                <div><dt class="text-muted">Logowanie</dt><dd class="font-bold text-ink">{{ $isMicrosoft ? 'Microsoft 365' : 'E-mail i hasło' }}</dd></div>
            </dl>
        </header>

        @if ($isMicrosoft)
            <div class="mb-6 flex items-start gap-3 rounded-lg bg-brand-light px-5 py-4 text-sm text-ink">
                <i class="fa-brands fa-microsoft mt-0.5 flex-none text-lg text-brand-dark" aria-hidden="true"></i>
                <div>
                    <p class="font-bold">Konto zarządzane przez Microsoft 365</p>
                    <p class="mt-0.5">Dane profilu, hasło i tożsamość zmieniasz w <strong>Systemie Zarządzania Organizacją</strong> w zakładce <strong>Tożsamość</strong>. Tutaj ustawisz edytor, wygląd panelu i powiadomienia.</p>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
            {{-- Spis sekcji --}}
            <nav aria-label="Sekcje profilu" class="lg:sticky lg:top-6 lg:self-start">
                <div class="rounded-lg bg-white p-3">
                    @foreach ($groups as $group => $items)
                        <p class="px-3 pb-1 {{ $loop->first ? 'pt-1' : 'pt-4' }} text-xs font-bold uppercase tracking-wide text-muted">{{ $group }}</p>
                        <ul role="list">
                            @foreach ($items as [$anchor, $title])
                                <li><a href="#{{ $anchor }}" class="{{ $navLink }} {{ $anchor === 'usun-konto' ? 'text-red-800 hover:text-red-800' : '' }}">{{ $title }}</a></li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </nav>

            {{-- Sekcje --}}
            <div class="space-y-6">
                @foreach ($sections as [$anchor, $title, $desc, $view])
                    <section id="{{ $anchor }}" aria-labelledby="{{ $anchor }}-h" class="scroll-mt-6 rounded-lg bg-white">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 id="{{ $anchor }}-h" class="text-base font-bold {{ $anchor === 'usun-konto' ? 'text-red-800' : 'text-ink' }}">{{ $title }}</h2>
                            <p class="mt-0.5 text-sm text-muted">{{ $desc }}</p>
                        </div>
                        <div class="p-6">
                            <div class="max-w-xl">
                                @if ($anchor === 'dane-profilu')
                                    @if ($isMicrosoft)
                                        <dl class="space-y-3 text-sm">
                                            <div><dt class="text-muted">Imię i nazwisko</dt><dd class="mt-0.5 font-bold text-ink">{{ $user->name ?: '—' }}</dd></div>
                                            <div><dt class="text-muted">E-mail</dt><dd class="mt-0.5 font-bold text-ink">{{ $user->email }}</dd></div>
                                        </dl>
                                    @else
                                        @include('profile.partials.update-profile-information-form')
                                    @endif
                                @else
                                    @include($view)
                                @endif
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection
