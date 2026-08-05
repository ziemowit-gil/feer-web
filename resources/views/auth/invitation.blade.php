<x-guest-layout>
    @section('title', 'Zaproszenie do strefy')
    @section('brand_heading', 'Zaproszenie do strefy')
    @section('brand_lead', 'Otrzymałeś/aś zaproszenie do strefy wewnętrznej.')

    <div class="mb-6 flex flex-col items-center text-center">
        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-brand">
            <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
        </span>
        <h1 class="text-xl font-bold text-ink">Dołącz do strefy</h1>
        <p class="mt-1 text-sm text-muted">
            Zaproszenie dla: <strong>{{ $invitation->email }}</strong>
        </p>
        @if ($invitation->note)
            <p class="mt-1 text-xs text-muted">{{ $invitation->note }}</p>
        @endif
    </div>

    @if (session('error'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="space-y-3">
        {{-- Opcja 1: MS365 --}}
        @if ($settings->memberLoginEnabled())
            <a href="{{ route('member.zaproszenie.microsoft', $token) }}"
                class="flex w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-bold text-ink shadow-sm transition hover:bg-gray-50 hover:shadow-md active:scale-[0.99]">
                <svg class="h-5 w-5" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                Zaloguj przez Microsoft 365
            </a>
        @endif

        {{-- Opcja 2: Magic link --}}
        <form method="POST" action="{{ route('member.zaproszenie.magic', $token) }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-brand bg-brand-light px-5 py-3 text-sm font-bold text-brand transition hover:bg-brand hover:text-white active:scale-[0.99]">
                <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                Wejdź bez logowania MS365
            </button>
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-muted">
        Ten link jest jednorazowy i przypisany do e-maila
        <strong>{{ $invitation->email }}</strong>.
        @if ($invitation->expires_at)
            Wygasa {{ $invitation->expires_at->locale('pl')->isoFormat('D MMMM YYYY, HH:mm') }}.
        @endif
    </p>
</x-guest-layout>
