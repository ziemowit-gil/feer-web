{{--
    Strona główna: „Ostatnie dni naboru" — do 3 opublikowanych naborów w toku
    (status „Nabór trwa"), które kończą się w ciągu 14 dni, oraz krótka lista
    zapytań ofertowych z terminem składania ofert w ciągu 7 dni. Najbliższy termin
    pierwszy. Gdy nic się nie kończy, sekcja się nie renderuje.
    Link „Zobacz wszystkie…" prowadzi do opublikowanej strony z shortcode [nabory]
    / [zapytania-ofertowe], jeśli taka istnieje. Płasko, tylko kolory firmowe.
--}}
@php
    $crNow = now();
    $crSoon = function (string $type, int $days, string $status, int $limit) use ($crNow) {
        return \App\Models\Page::forCurrentSite()->live()->ofType($type)
            ->where('is_disabled', false)
            ->get()
            ->filter(function (\App\Models\Page $p) use ($status, $days, $crNow) {
                $deadline = $p->listingDeadline();

                return $p->listingStatus() === $status && $deadline
                    && $deadline->greaterThanOrEqualTo($crNow) && $deadline->lessThanOrEqualTo($crNow->copy()->addDays($days)->endOfDay())
                    && ! $p->requiresAccess() && ! $p->isDisabled();
            })
            ->sortBy(fn (\App\Models\Page $p) => $p->listingDeadline()->getTimestamp())
            ->take($limit)
            ->values();
    };
    $crEnabled = $siteSettings->isModuleEnabled('pages');
    $crRecruitments = $crEnabled ? $crSoon('recruitment', 14, 'open', 3) : collect();
    $crTenders = $crEnabled ? $crSoon('tender', 7, 'open', 3) : collect();

    $crListPage = function (string $shortcode) {
        return \App\Models\Page::forCurrentSite()->live()
            ->where('is_disabled', false)
            ->where('content', 'like', '%['.$shortcode.']%')
            ->orderBy('id')
            ->first();
    };
    $crRecruitmentsPage = $crRecruitments->isNotEmpty() ? $crListPage('nabory') : null;
    $crTendersPage = $crTenders->isNotEmpty() ? $crListPage('zapytania-ofertowe') : null;

    // „Do końca: 3 dni" — dni kalendarzowe jak na stronie naboru (0 = dziś ostatni dzień).
    $crLeft = function (\Illuminate\Support\Carbon $deadline) use ($crNow) {
        $days = (int) $crNow->copy()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        return match (true) {
            $days <= 0 => 'dziś ostatni dzień',
            $days === 1 => 'jutro ostatni dzień',
            default => 'zostało '.$days.' dni',
        };
    };
    $crFmt = fn (\Illuminate\Support\Carbon $d) => $d->copy()->locale('pl')->translatedFormat($d->format('H:i') === '23:59' ? 'j F Y' : 'j F Y, \g\o\d\z. H:i');
    $crLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

@if ($crRecruitments->isNotEmpty() || $crTenders->isNotEmpty())
<section id="konczace-sie-nabory" class="py-12" aria-labelledby="closing-recruitments-heading">
    <div class="mx-auto max-w-6xl px-4">
        <h2 id="closing-recruitments-heading" class="mb-8 text-2xl font-bold text-ink md:text-3xl">
            {{ $crRecruitments->isNotEmpty() ? 'Ostatnie dni naboru' : 'Kończące się zapytania ofertowe' }}
        </h2>

        @if ($crRecruitments->isNotEmpty())
            <ul class="grid gap-6 md:grid-cols-3" role="list">
                @foreach ($crRecruitments as $crItem)
                    @php $crEnd = $crItem->listingDeadline(); $crLead = $crItem->typeData()['lead'] ?? null; @endphp
                    <li class="flex flex-col rounded-md bg-brand-light p-6">
                        <h3 class="text-lg font-bold leading-snug text-ink">
                            <a href="{{ site_route('page.show', $crItem) }}" class="{{ $crLink }}">{{ $crItem->title }}</a>
                        </h3>
                        <p class="mt-3 flex items-start gap-2 font-semibold text-ink">
                            <i class="fa-solid fa-bell mt-1 text-brand-dark" aria-hidden="true"></i>
                            <span>Nabór do <time datetime="{{ $crEnd->toIso8601String() }}">{{ $crFmt($crEnd) }}</time> — {{ $crLeft($crEnd) }}</span>
                        </p>
                        @if (filled($crLead))
                            <p class="mt-3 leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($crLead, 160) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($crRecruitmentsPage)
                <p class="mt-6">
                    <a href="{{ site_route('page.show', $crRecruitmentsPage) }}" class="{{ $crLink }}">Zobacz wszystkie nabory <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i></a>
                </p>
            @endif
        @endif

        @if ($crTenders->isNotEmpty())
            @if ($crRecruitments->isNotEmpty())
                <h3 class="mb-4 mt-10 text-xl font-bold text-ink">Zapytania ofertowe — termin składania ofert mija wkrótce</h3>
            @endif
            <ul class="grid gap-3" role="list">
                @foreach ($crTenders as $crItem)
                    @php $crEnd = $crItem->listingDeadline(); $crNumber = $crItem->typeData()['tender_number'] ?? null; @endphp
                    <li class="rounded-md bg-gray-50 px-6 py-4">
                        <a href="{{ site_route('page.show', $crItem) }}" class="{{ $crLink }}">{{ $crItem->title }}</a>
                        <span class="block text-ink sm:inline">
                            <span class="hidden sm:inline" aria-hidden="true"> · </span>@if (filled($crNumber))znak sprawy {{ $crNumber }}, @endif oferty do <time datetime="{{ $crEnd->toIso8601String() }}">{{ $crFmt($crEnd) }}</time>
                        </span>
                    </li>
                @endforeach
            </ul>
            @if ($crTendersPage)
                <p class="mt-6">
                    <a href="{{ site_route('page.show', $crTendersPage) }}" class="{{ $crLink }}">Zobacz wszystkie zapytania ofertowe <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i></a>
                </p>
            @endif
        @endif
    </div>
</section>
@endif
