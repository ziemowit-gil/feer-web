{{--
    Pasek przełączenia na cudze konto (Admin\ImpersonationController): przyklejony do dołu ekranu w panelu i na stronie,
    żeby nie zasłaniał nagłówków. Ciemne tło i biały tekst (kontrast 17:1), przycisk „Wróć do swojego konta” zawsze pod ręką.
--}}
@php $impAdminId = session(\App\Http\Controllers\Admin\ImpersonationController::SESSION_KEY); @endphp
@if ($impAdminId && auth('web')->check())
    @php
        $impUser = auth('web')->user();
        $impAdmin = \App\Models\User::find($impAdminId);
    @endphp
    <style>
        .impersonation-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10000; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem 1rem; padding: .625rem 1rem; background: #1d1d1a; color: #fff; font: 600 .9375rem/1.4 'Montserrat', ui-sans-serif, system-ui, sans-serif; box-shadow: 0 -2px 12px rgba(0, 0, 0, .25); }
        .impersonation-bar p { margin: 0; }
        .impersonation-bar strong { font-weight: 800; }
        .impersonation-bar button { min-height: 2.5rem; padding: .4rem 1rem; border: 0; border-radius: 9999px; background: #fff; color: #1d1d1a; font: inherit; font-weight: 800; cursor: pointer; }
        .impersonation-bar button:hover { background: #e5e7eb; }
        .impersonation-bar button:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
        .impersonation-spacer { height: 4rem; }
    </style>
    <div class="impersonation-spacer" aria-hidden="true"></div>
    <div class="impersonation-bar" role="region" aria-label="Przełączone konto">
        <p><i class="fa-solid fa-user-secret" aria-hidden="true"></i>
            Pracujesz jako <strong>{{ $impUser->name }}</strong> ({{ \App\Models\User::ROLES[$impUser->role] ?? $impUser->role }}) — widzisz wszystko tak jak ta osoba.@if ($impAdmin) Twoje konto: {{ $impAdmin->name }}.@endif
        </p>
        <form method="POST" action="{{ route('admin.przelacz.wroc') }}">
            @csrf
            <button type="submit">Wróć do swojego konta</button>
        </form>
    </div>
@endif
