@auth
@if (auth()->user()->isAdmin() || auth()->user()->user_group_id)
@php
    $abl = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40';
@endphp

<div x-data="{ open: false }"
     @keydown.escape.window="open = false">

    {{-- Przycisk wyzwalający --}}
    <button type="button"
        @click="open = true"
        class="fixed bottom-5 right-5 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-gray-900 text-white shadow-lg ring-1 ring-white/10 transition hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
        aria-label="Otwórz panel administracyjny"
        :aria-expanded="open">
        <i class="fa-solid fa-screwdriver-wrench text-sm" aria-hidden="true"></i>
    </button>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 z-40 bg-black/40"
         aria-hidden="true"
         style="display:none">
    </div>

    {{-- Drawer --}}
    <nav x-show="open"
         x-transition:enter="transition duration-250 ease-out"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-gray-900 text-white shadow-2xl"
         role="navigation"
         aria-label="Panel administracyjny"
         style="display:none">

        {{-- Nagłówek drawera --}}
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 text-white hover:text-white/80">
                <i class="fa-solid fa-gauge text-brand" aria-hidden="true"></i>
                <span class="leading-tight">
                    <span style="font-family:'Pacifico',cursive">We</span><span style="font-family:'Lato',sans-serif;font-weight:700">CMS</span>
                    <span class="block text-[10px] font-normal text-white/50">Autorski CMS dla NGO</span>
                </span>
            </a>
            <button type="button" @click="open = false"
                class="flex h-8 w-8 items-center justify-center rounded text-white/50 hover:bg-white/10 hover:text-white"
                aria-label="Zamknij panel">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Linki --}}
        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">

            @if ($siteSettings->isModuleEnabled('news') && (auth()->user()->canAccessModule('news')))
                <a href="{{ route('admin.newsy.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-newspaper w-5 text-center text-white/40" aria-hidden="true"></i>
                    Aktualności
                </a>
            @endif

            @if ($siteSettings->isModuleEnabled('hero'))
                <a href="{{ route('admin.hero.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-images w-5 text-center text-white/40" aria-hidden="true"></i>
                    Slider (hero)
                </a>
            @endif

            @if ($siteSettings->isModuleEnabled('gallery'))
                <a href="{{ route('admin.galeria.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-panorama w-5 text-center text-white/40" aria-hidden="true"></i>
                    Galeria
                </a>
            @endif

            <a href="{{ route('admin.multimedia.index') }}" class="{{ $abl }}">
                <i class="fa-solid fa-photo-film w-5 text-center text-white/40" aria-hidden="true"></i>
                Multimedia
            </a>

            @if ($siteSettings->isModuleEnabled('events') && auth()->user()->canAccessModule('events'))
                <a href="{{ route('admin.wydarzenia.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-calendar-days w-5 text-center text-white/40" aria-hidden="true"></i>
                    Szkolenia i wydarzenia
                </a>
            @endif

            @if ($siteSettings->isModuleEnabled('news') && auth()->user()->canAccessModule('news'))
                <a href="{{ route('admin.kalendarz.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-white/40" aria-hidden="true"></i>
                    Kalendarz redakcyjny
                </a>
            @endif

            @if (auth()->user()->canApproveContent())
                <a href="{{ route('admin.zatwierdzanie.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-clipboard-check w-5 text-center text-white/40" aria-hidden="true"></i>
                    Do zatwierdzenia
                </a>
            @endif

            @if (auth()->user()->isAdmin())
                <div class="my-2 border-t border-white/10"></div>
                <a href="{{ route('admin.podstrony.index') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-file-lines w-5 text-center text-white/40" aria-hidden="true"></i>
                    Strony i menu
                </a>
                <a href="{{ route('admin.ustawienia.edit') }}" class="{{ $abl }}">
                    <i class="fa-solid fa-gear w-5 text-center text-white/40" aria-hidden="true"></i>
                    Ustawienia
                </a>
            @endif

        </div>

        {{-- Stopka: użytkownik + wyloguj --}}
        <div class="border-t border-white/10 px-4 py-3">
            <div class="mb-2 truncate text-xs text-white/40">{{ auth()->user()->name }} &lt;{{ auth()->user()->email }}&gt;</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $abl }} w-full">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center text-white/40" aria-hidden="true"></i>
                    Wyloguj się
                </button>
            </form>
        </div>
    </nav>
</div>
@endif
@endauth
