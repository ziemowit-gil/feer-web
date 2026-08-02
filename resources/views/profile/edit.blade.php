@extends('admin.layout')

@section('title', 'Mój profil')

@section('content')
    @php $isMicrosoft = (bool) auth()->user()->microsoft_id; @endphp

    <div class="mx-auto max-w-4xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-ink">Mój profil</h1>
            <p class="mt-1 text-sm text-muted">Zarządzaj danymi konta, hasłem i logowaniem dwuetapowym.</p>
        </div>

        @if ($isMicrosoft)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
                <i class="fa-brands fa-microsoft mt-0.5 flex-none text-lg text-blue-500" aria-hidden="true"></i>
                <div>
                    <p class="font-bold">Konto zarządzane przez Microsoft 365</p>
                    <p class="mt-0.5 text-blue-800">Dane profilu, hasło i tożsamość należy zmieniać w <strong>Systemie Zarządzania Organizacją</strong> w zakładce <strong>Tożsamość</strong>. Tutaj możesz zarządzać wyłącznie powiadomieniami.</p>
                </div>
            </div>
        @endif

        <div class="divide-y divide-gray-200">

            {{-- Dane profilu --}}
            <div class="grid gap-8 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Dane profilu</h2>
                    <p class="mt-1 text-sm text-muted">Imię wyświetlane w panelu i adres e-mail do logowania.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    @if ($isMicrosoft)
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Imię i nazwisko</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ auth()->user()->name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">E-mail</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ auth()->user()->email }}</dd>
                            </div>
                        </dl>
                    @else
                        <div class="max-w-md">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    @endif
                </div>
            </div>

            {{-- Hasło --}}
            @if (! $isMicrosoft)
            <div class="grid gap-8 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Hasło</h2>
                    <p class="mt-1 text-sm text-muted">Używaj długiego, losowego hasła dla bezpieczeństwa konta.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="max-w-md">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
            @endif

            {{-- 2FA --}}
            @if (! $isMicrosoft)
            <div id="dwuetapowe" class="grid gap-8 scroll-mt-6 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Logowanie dwuetapowe</h2>
                    <p class="mt-1 text-sm text-muted">Dodatkowe zabezpieczenie przy logowaniu hasłem.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    @include('profile.partials.two-factor-form')
                </div>
            </div>
            @endif

            {{-- Powiadomienia --}}
            <div id="powiadomienia" class="grid gap-8 scroll-mt-6 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Powiadomienia</h2>
                    <p class="mt-1 text-sm text-muted">Wybierz, o czym chcesz dostawać e-maile.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="max-w-md">
                        @include('profile.partials.notification-preferences-form')
                    </div>
                </div>
            </div>

            {{-- Usuń konto --}}
            @if (! $isMicrosoft)
            <div class="grid gap-8 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-red-600">Usuń konto</h2>
                    <p class="mt-1 text-sm text-muted">Nieodwracalne usunięcie konta i wszystkich danych.</p>
                </div>
                <div class="rounded-lg border border-red-200 bg-white p-6">
                    <div class="max-w-md">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
@endsection
