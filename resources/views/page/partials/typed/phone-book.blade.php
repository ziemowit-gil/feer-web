{{--
    Typ „Książka telefoniczna": kto, numer komórkowy i w jakiej sprawie się kontaktować.
    Osoby grupowane po polu „Dział" (kolejność jak w panelu), filtr tekstowy (Alpine, bez przeładowania)
    po imieniu, roli, sprawie i numerze. Numer to link tel: (na telefonie dzwoni jednym dotknięciem).
    Płasko, kolory firmowe; tekst ink na szarym tle ≥ 14:1, cele dotyku ≥ 44 px.
--}}
@php
    $td = $page->typeData();
    $people = collect($td['contacts'] ?? [])
        ->filter(fn ($c) => filled($c['name'] ?? null) || filled($c['phone'] ?? null))
        ->map(fn ($c) => [
            'group' => trim((string) ($c['group'] ?? '')),
            'name' => trim((string) ($c['name'] ?? '')),
            'role' => trim((string) ($c['role'] ?? '')),
            'phone' => trim((string) ($c['phone'] ?? '')),
            'tel' => preg_replace('/[^0-9+]/', '', (string) ($c['phone'] ?? '')),
            'email' => trim((string) ($c['email'] ?? '')),
            'topic' => trim((string) ($c['topic'] ?? '')),
        ])
        ->values();
    $groups = $people->groupBy('group');
    $link = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="phone_book" data-phone-book x-data="{ q: '' }">
    @include('page.partials.typed._head', ['print' => true])

    @if ($page->content)
        <div class="prose mb-8 max-w-3xl text-ink">@shortcodes($page->content)</div>
    @endif

    @if ($people->isEmpty())
        <p class="text-ink">Książka telefoniczna nie ma jeszcze wpisów. Dodaj osoby w panelu (edycja strony → Książka telefoniczna).</p>
    @else
        <div class="mb-8 max-w-xl rounded-md bg-brand-light p-5">
            <label for="phonebook-q" class="mb-2 block font-bold text-ink">Znajdź osobę albo sprawę</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink" aria-hidden="true"></i>
                <input id="phonebook-q" type="search" x-model.debounce.150ms="q" placeholder="np. faktury, zapisy, imię" autocomplete="off"
                       class="min-h-11 w-full rounded-md bg-white pl-9 text-base text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand-dark">
            </div>
        </div>

        <p class="sr-only" aria-live="polite" x-text="q.trim() ? ('Znaleziono osób: ' + [...$el.closest('[data-phone-book]').querySelectorAll('[data-person]')].filter(el => el.dataset.person.includes(q.trim().toLowerCase())).length) : ''"></p>

        @foreach ($groups as $groupName => $list)
            <section class="{{ $loop->first ? '' : 'mt-10' }}" @if ($groupName !== '') aria-labelledby="phonebook-group-{{ $loop->index }}" @endif
                     x-show="q.trim() === '' || [...$el.querySelectorAll('[data-person]')].some(el => el.dataset.person.includes(q.trim().toLowerCase()))">
                @if ($groupName !== '')
                    <h2 id="phonebook-group-{{ $loop->index }}" class="mb-4 text-2xl font-bold text-ink">{{ $groupName }}</h2>
                @endif
                <ul class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($list as $p)
                        <li class="rounded-md bg-gray-50 p-5" data-person="{{ mb_strtolower($p['name'].' '.$p['role'].' '.$p['topic'].' '.$p['phone'].' '.$p['group']) }}"
                            x-show="q.trim() === '' || $el.dataset.person.includes(q.trim().toLowerCase())">
                            @if ($p['name'] !== '')
                                <p class="text-lg font-bold text-ink">{{ $p['name'] }}</p>
                            @endif
                            @if ($p['role'] !== '')
                                <p class="text-ink">{{ $p['role'] }}</p>
                            @endif
                            @if ($p['topic'] !== '')
                                <p class="mt-3 leading-relaxed text-ink"><span class="font-bold">W sprawie:</span> {{ $p['topic'] }}</p>
                            @endif
                            @if ($p['phone'] !== '')
                                <p class="mt-3"><a href="tel:{{ $p['tel'] }}" class="{{ $link }} text-xl"><i class="fa-solid fa-phone" aria-hidden="true"></i><span class="sr-only">Zadzwoń do {{ $p['name'] ?: 'tej osoby' }}: </span>{{ $p['phone'] }}</a></p>
                            @endif
                            @if ($p['email'] !== '')
                                <p><a href="mailto:{{ $p['email'] }}" class="{{ $link }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $p['email'] }}</a></p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <p class="mt-8 text-ink" x-show="q.trim() !== '' && [...$el.closest('[data-phone-book]').querySelectorAll('[data-person]')].every(el => !el.dataset.person.includes(q.trim().toLowerCase()))" x-cloak>
            Brak osób pasujących do „<span x-text="q"></span>".
        </p>
    @endif

    @include('partials.attachments-list', ['attachments' => $page->attachments])
    @include('partials.page-section-nav', ['page' => $page])
</section>
