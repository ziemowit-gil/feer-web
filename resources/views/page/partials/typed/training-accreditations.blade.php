{{-- Typ „Akredytacje i jakość kształcenia”: wpisy i certyfikaty (z ważnością), wskaźniki jakości, polityka jakości (treść strony). --}}
@php
    $td = $page->typeData();
    $safeUrl = fn ($u) => preg_match('~^(https?://|/(?!/)|#)~i', trim((string) $u)) ? trim((string) $u) : '';
@endphp

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="training_accreditations">
    @include('page.partials.typed._head', ['print' => false])
    @include('partials.page-content-image')

    @if ($td['quality_stats'])
        <ul class="mb-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
            @foreach ($td['quality_stats'] as $row)
                <li class="rounded-md bg-brand-light p-5">
                    <p class="text-3xl font-bold leading-none text-brand-dark">{{ $row['value'] ?? '' }}</p>
                    <p class="mt-2 text-ink">{{ $row['label'] ?? '' }}</p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($td['accreditations'])
        <section aria-labelledby="ta-list">
            <h2 id="ta-list" class="mb-4 text-2xl font-bold text-ink">Akredytacje i certyfikaty</h2>
            <ul class="grid gap-4 md:grid-cols-2" role="list">
                @foreach ($td['accreditations'] as $row)
                    @php
                        $until = filled($row['valid_until'] ?? null) ? \Illuminate\Support\Carbon::parse($row['valid_until']) : null;
                        $docUrl = $safeUrl($row['url'] ?? '');
                    @endphp
                    <li class="rounded-md bg-gray-50 p-5">
                        <h3 class="text-lg font-bold text-ink">@if ($docUrl !== '')<a href="{{ $docUrl }}" class="text-brand-dark underline underline-offset-4 hover:no-underline">{{ $row['name'] ?? '' }}</a>@else{{ $row['name'] ?? '' }}@endif</h3>
                        <p class="mt-1 text-sm text-ink">{{ collect([$row['issuer'] ?? null, filled($row['number'] ?? null) ? 'nr '.$row['number'] : null])->filter()->implode(' · ') }}</p>
                        @if ($until)<p class="mt-1 text-sm font-semibold text-ink">{{ $until->isPast() ? 'Wygasło' : 'Ważne do' }} <time datetime="{{ $until->toDateString() }}">{{ $until->translatedFormat('j F Y') }}</time></p>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($page->content)<div class="prose mt-10 max-w-none text-ink">@shortcodes($page->content)</div>@endif
    @include('partials.attachments-list', ['attachments' => $page->attachments])
</section>
