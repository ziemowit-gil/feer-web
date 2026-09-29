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
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
        @if ($hasAccount)
            @php $highlight = $siteSettings->wide_mission_highlight_account || $onBar; @endphp
            <span class="inline-flex min-h-9 items-center gap-1.5 {{ $highlight ? 'text-brand' : 'text-muted' }}">
                <span class="font-medium {{ $highlight ? '' : 'text-ink' }}">Nr konta:</span>
                <span class="font-mono {{ $highlight ? 'font-bold' : '' }} tracking-wide">{{ $siteSettings->bank_account_number }}</span>
            </span>
        @endif

        @if ($hasSupport)
            <a href="{{ route('support.show') }}"
                class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2 font-bold text-brand underline-offset-4 transition hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-heart text-xs" aria-hidden="true"></i>
                Wesprzyj naszą działalność
            </a>
        @endif
    </div>
@endif
