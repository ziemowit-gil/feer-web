{{--
    Numer konta + link „Wesprzyj naszą działalność" z nagłówka „Szeroka belka".
    Ten sam blok stoi w prawej kolumnie (układ „right") albo w osobnym pasku
    nad belką (układ „bar") — stąd wspólny partial.

    Zmienne (opcjonalne): $onBar — true dla paska nad belką.
--}}
@php
    $onBar = $onBar ?? false;
    $hasAccount = filled($siteSettings->bank_account_number);
    $hasSupport = \Illuminate\Support\Facades\Route::has('support.show');
@endphp

@if ($hasAccount || $hasSupport)
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 {{ $onBar ? 'text-xs' : 'text-xs text-muted' }}">
        @if ($hasAccount)
            @if ($siteSettings->wide_mission_highlight_account || $onBar)
                <span class="font-medium text-brand">Nr konta:
                    <span class="font-mono font-bold tracking-wide">{{ $siteSettings->bank_account_number }}</span>
                </span>
            @else
                <span>
                    <span class="font-medium text-ink">Nr konta:</span>
                    <span class="font-mono tracking-wide">{{ $siteSettings->bank_account_number }}</span>
                </span>
            @endif
        @endif

        @if ($hasSupport)
            <a href="{{ route('support.show') }}"
                class="flex min-h-6 items-center gap-1 rounded px-1 font-bold text-brand transition hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <i class="fa-solid fa-heart text-[10px]" aria-hidden="true"></i>
                Wesprzyj naszą działalność
            </a>
        @endif
    </div>
@endif
