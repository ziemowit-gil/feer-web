@extends('admin.layout')

@section('title', 'Mój profil')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-ink">Mój profil</h1>
            <p class="mt-1 text-sm text-muted">Zarządzaj danymi konta, hasłem i logowaniem dwuetapowym.</p>
        </div>

        <div class="divide-y divide-gray-200">

            {{-- Dane profilu --}}
            <div class="grid gap-8 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Dane profilu</h2>
                    <p class="mt-1 text-sm text-muted">Imię wyświetlane w panelu i adres e-mail do logowania.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="max-w-md">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- Hasło --}}
            <div class="grid gap-8 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Hasło</h2>
                    <p class="mt-1 text-sm text-muted">Używaj długiego, losowego hasła dla bezpieczeństwa konta.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    @if (auth()->user()->microsoft_id)
                        <div class="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                            <i class="fa-solid fa-circle-info mt-0.5 flex-none text-blue-500" aria-hidden="true"></i>
                            <p>Twoje konto używa logowania przez <strong>Microsoft 365</strong>. Hasło należy zmienić w <strong>Systemie Zarządzania Organizacją</strong> w zakładce <strong>Tożsamość</strong>.</p>
                        </div>
                    @else
                        <div class="max-w-md">
                            @include('profile.partials.update-password-form')
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2FA --}}
            <div id="dwuetapowe" class="grid gap-8 scroll-mt-6 py-8 md:grid-cols-[220px_1fr]">
                <div>
                    <h2 class="text-base font-bold text-ink">Logowanie dwuetapowe</h2>
                    <p class="mt-1 text-sm text-muted">Dodatkowe zabezpieczenie przy logowaniu hasłem.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    @include('profile.partials.two-factor-form')
                </div>
            </div>

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

        </div>
    </div>
@endsection
