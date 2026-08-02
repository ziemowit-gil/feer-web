@auth
@if (auth()->user()->isAdmin() || auth()->user()->user_group_id)
<div class="bg-gray-900 text-white" role="navigation" aria-label="Pasek administracyjny">
    <div class="mx-auto flex max-w-6xl items-center gap-1 overflow-x-auto px-4">

        {{-- Lewa: logo/label --}}
        <a href="{{ route('admin.dashboard') }}"
            class="flex shrink-0 items-center gap-2 border-r border-white/10 py-1.5 pr-3 text-xs font-bold text-white/80 hover:text-white">
            <i class="fa-solid fa-gauge w-3 text-center" aria-hidden="true"></i>
            <span class="hidden sm:inline">Panel admina</span>
        </a>

        {{-- Środek: szybkie linki --}}
        <div class="flex items-center gap-0.5 overflow-x-auto">
            @php
                $abl = 'flex shrink-0 items-center gap-1.5 rounded px-2.5 py-1.5 text-xs text-white/70 transition hover:bg-white/10 hover:text-white';
            @endphp

            @if (auth()->user()->canAccessModule('news') ?? true)
                <a href="{{ route('admin.newsy.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-newspaper w-3 text-center" aria-hidden="true"></i>
                    <span>Aktualności</span>
                </a>
            @endif

            @if ($siteSettings->isModuleEnabled('hero'))
                <a href="{{ route('admin.hero.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-images w-3 text-center" aria-hidden="true"></i>
                    <span>Slider</span>
                </a>
            @endif

            <a href="{{ route('admin.multimedia.index') }}" class="{{ $abl }}">
                <i class="fa-solid fa-photo-film w-3 text-center" aria-hidden="true"></i>
                <span>Multimedia</span>
            </a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.ustawienia.edit') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-gear w-3 text-center" aria-hidden="true"></i>
                    <span>Ustawienia</span>
                </a>
            @endif
        </div>

        {{-- Prawa: użytkownik + wyloguj --}}
        <div class="ml-auto flex shrink-0 items-center gap-1 border-l border-white/10 pl-3">
            <span class="hidden max-w-[10rem] truncate text-xs text-white/50 md:block">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-xs text-white/70 transition hover:bg-white/10 hover:text-white"
                    aria-label="Wyloguj się z panelu">
                    <i class="fa-solid fa-right-from-bracket w-3 text-center" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Wyloguj</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endauth
