{{--
    Menu boczne strony kontaktu w szablonie FEER (jak menu boczne działania): szare pole, kreska marki pod tytułem,
    bieżąca pozycja na białym i pogrubiona (aria-current="page"). Pozycje: strona Kontakt, automatyczne podstrony
    z ustawień (App\Support\ContactSections) i opublikowane podstrony strony Kontakt dodane przez redakcję.
    Zmienne: $current (slug podstrony albo null na stronie głównej kontaktu), $showMeetings, $showShipping, $metkaPage.
    Zwraca nic, gdy nie ma żadnej podstrony (strona zostaje bez menu).
--}}
@php
    $navSections = \App\Support\ContactSections::available($siteSettings, $showMeetings, $showShipping);
    $navSubpages = ($metkaPage ?? null)?->publishedChildren()->get() ?? collect();
    $navHome = ($metkaPage ?? null)?->publicUrl() ?? route('contact.show');
    $navItem = 'flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-[0.9375rem] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark ';
    $navIdle = 'text-ink hover:bg-white hover:text-brand-dark';
    $navCurrent = 'bg-white font-bold text-brand-dark';
    $current = $current ?? null;
@endphp
@if ($navSections || $navSubpages->isNotEmpty())
    <nav aria-label="Menu strony kontaktu" class="rounded-md bg-gray-50 p-5">
        <p class="text-base font-bold leading-snug text-ink">Kontakt</p>
        <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="space-y-0.5">
            <li>
                <a href="{{ $navHome }}" @if ($current === null) aria-current="page" @endif class="{{ $navItem }}{{ $current === null ? $navCurrent : $navIdle }}">
                    <span class="min-w-0">Dane i formularz</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </a>
            </li>
            @foreach ($navSections as $slug => $label)
                <li>
                    <a href="{{ route('contact.section', $slug) }}" @if ($current === $slug) aria-current="page" @endif class="{{ $navItem }}{{ $current === $slug ? $navCurrent : $navIdle }}">
                        <span class="min-w-0">{{ $label }}</span>
                        <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
            @foreach ($navSubpages as $sub)
                <li>
                    <a href="{{ $sub->publicUrl() }}" class="{{ $navItem }}{{ $navIdle }}">
                        <span class="min-w-0">{{ $sub->title }}</span>
                        <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
