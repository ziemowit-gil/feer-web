{{--
    Lista książki telefonicznej (wspólna dla typu strony „Książka telefoniczna” i modułu): kto, numer komórkowy,
    w jakiej sprawie i kiedy dzwonić. Układ tabelaryczny (każdy dział to tabela) albo karty, filtr działów, wyszukiwarka,
    kopiowanie numeru, zapis kontaktu .vcf i opcjonalne ukrywanie numerów za przyciskiem.
    Zmienne: $contacts (tablice: group, name, role, phone, email, topic, hours), $layout (table|cards),
    $sort (manual|alpha), $hide (bool). Płasko, kolory firmowe; tekst ink na szarym tle ≥ 14:1, cele dotyku ≥ 44 px.
--}}
@php
    $alpha = ($sort ?? 'manual') === 'alpha';
    $hideNumbers = ! empty($hide);
    $asTable = ($layout ?? 'table') !== 'cards';
    $people = collect($contacts ?? [])
        ->filter(fn ($c) => filled($c['name'] ?? null) || filled($c['phone'] ?? null))
        ->map(fn ($c) => [
            'group' => trim((string) ($c['group'] ?? '')),
            'name' => trim((string) ($c['name'] ?? '')),
            'role' => trim((string) ($c['role'] ?? '')),
            'phone' => trim((string) ($c['phone'] ?? '')),
            'tel' => preg_replace('/[^0-9+]/', '', (string) ($c['phone'] ?? '')),
            'email' => trim((string) ($c['email'] ?? '')),
            'topic' => trim((string) ($c['topic'] ?? '')),
            'hours' => trim((string) ($c['hours'] ?? '')),
        ])
        ->values();
    $anyHours = $people->contains(fn ($p) => $p['hours'] !== '');
    if ($alpha) {
        $people = $people->sortBy(fn ($p) => mb_strtolower($p['name'] ?: $p['phone']), SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
    $groups = $people->groupBy('group');
    if ($alpha) {
        $groups = $groups->sortBy(fn ($list, $name) => $name === '' ? '' : mb_strtolower($name), SORT_NATURAL | SORT_FLAG_CASE);
    }
    $groupNames = $groups->keys()->filter(fn ($g) => $g !== '')->values();

    // Karta kontaktu .vcf (vCard 3.0), tworzona w przeglądarce z tego tekstu.
    $vEsc = fn (string $v) => str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\;'], $v);
    $vcard = fn (array $p) => implode("\r\n", array_filter([
        'BEGIN:VCARD', 'VERSION:3.0',
        'FN:'.$vEsc($p['name'] ?: $p['phone']),
        'N:'.$vEsc($p['name'] ?: $p['phone']).';;;;',
        $p['phone'] !== '' ? 'TEL;TYPE=CELL:'.$p['tel'] : null,
        $p['email'] !== '' ? 'EMAIL:'.$vEsc($p['email']) : null,
        $p['role'] !== '' ? 'TITLE:'.$vEsc($p['role']) : null,
        'ORG:'.$vEsc((string) $siteSettings->site_name),
        $p['topic'] !== '' ? 'NOTE:'.$vEsc('W sprawie: '.$p['topic']) : null,
        'END:VCARD',
    ])) . "\r\n";

    $link = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $act = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-3 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

<div data-phone-book
    x-data="{
        q: '', g: '', copied: '',
        match(el) { const t = this.q.trim().toLowerCase(); return (this.g === '' || el.dataset.group === this.g) && (t === '' || el.dataset.person.includes(t)); },
        count() { return [...this.$el.querySelectorAll('[data-person]')].filter((el) => this.match(el)).length; },
        async copy(number, name) {
            try { await navigator.clipboard.writeText(number); this.copied = 'Skopiowano numer' + (name ? ': ' + name : ''); }
            catch (e) { this.copied = 'Nie udało się skopiować. Zaznacz numer ręcznie.'; }
            setTimeout(() => { this.copied = ''; }, 4000);
        },
        save(card, file) {
            const url = URL.createObjectURL(new Blob([card], { type: 'text/vcard;charset=utf-8' }));
            const a = document.createElement('a'); a.href = url; a.download = file; document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }
    }">
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

        @if ($groupNames->count() > 1)
            <div class="mb-8 flex flex-wrap gap-2 no-print" role="group" aria-label="Filtr działów">
                @php $chip = 'min-h-11 rounded-full px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark'; @endphp
                <button type="button" @click="g = ''" :aria-pressed="g === ''" :class="g === '' ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-brand-light'" class="{{ $chip }}">Wszystkie</button>
                @foreach ($groupNames as $gn)
                    <button type="button" @click="g = @js($gn)" :aria-pressed="g === @js($gn)" :class="g === @js($gn) ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-brand-light'" class="{{ $chip }}">{{ $gn }}</button>
                @endforeach
            </div>
        @endif

        <p class="sr-only" aria-live="polite" x-text="(q.trim() || g) ? ('Znaleziono osób: ' + count()) : ''"></p>
        <p class="mb-4 text-ink" role="status" x-show="copied" x-text="copied" x-cloak></p>

        @foreach ($groups as $groupName => $list)
            <section class="{{ $loop->first ? '' : 'mt-10' }}" @if ($groupName !== '') aria-labelledby="phonebook-group-{{ $loop->index }}" @endif
                     x-show="[...$el.querySelectorAll('[data-person]')].some((el) => match(el))">
                @if ($groupName !== '')
                    <h2 id="phonebook-group-{{ $loop->index }}" class="mb-4 text-2xl font-bold text-ink">{{ $groupName }}</h2>
                @endif
                @if ($asTable)
                    <table class="phone-book-table w-full text-left text-ink">
                        <caption class="sr-only">{{ $groupName !== '' ? $groupName : 'Osoby' }}: kto, numer telefonu, w jakiej sprawie{{ $anyHours ? ' i kiedy dzwonić' : '' }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Osoba</th>
                                <th scope="col">Numer komórkowy</th>
                                <th scope="col">W jakiej sprawie</th>
                                @if ($anyHours)<th scope="col">Kiedy dzwonić</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list as $p)
                                <tr data-group="{{ $p['group'] }}" data-person="{{ mb_strtolower($p['name'].' '.$p['role'].' '.$p['topic'].' '.$p['phone'].' '.$p['group'].' '.$p['hours']) }}" x-show="match($el)">
                                    <th scope="row" data-label="Osoba">
                                        <span class="block text-lg font-bold">{{ $p['name'] !== '' ? $p['name'] : $p['phone'] }}</span>
                                        @if ($p['role'] !== '')<span class="block font-normal">{{ $p['role'] }}</span>@endif
                                        @if ($p['email'] !== '')<a href="mailto:{{ $p['email'] }}" class="{{ $link }} font-normal"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $p['email'] }}</a>@endif
                                    </th>
                                    <td data-label="Numer komórkowy">
                                        @include('page.partials.typed._phone-book-number', ['p' => $p, 'hideNumbers' => $hideNumbers, 'link' => $link, 'act' => $act, 'vcard' => $vcard, 'wrap' => ''])
                                    </td>
                                    <td data-label="W jakiej sprawie">{{ $p['topic'] }}</td>
                                    @if ($anyHours)<td data-label="Kiedy dzwonić">{{ $p['hours'] }}</td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                <ul class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($list as $p)
                        <li class="rounded-md bg-gray-50 p-5" data-group="{{ $p['group'] }}" data-person="{{ mb_strtolower($p['name'].' '.$p['role'].' '.$p['topic'].' '.$p['phone'].' '.$p['group'].' '.$p['hours']) }}"
                            x-show="match($el)">
                            @if ($p['name'] !== '')
                                <p class="text-lg font-bold text-ink">{{ $p['name'] }}</p>
                            @endif
                            @if ($p['role'] !== '')
                                <p class="text-ink">{{ $p['role'] }}</p>
                            @endif
                            @if ($p['topic'] !== '')
                                <p class="mt-3 leading-relaxed text-ink"><span class="font-bold">W sprawie:</span> {{ $p['topic'] }}</p>
                            @endif
                            @if ($p['hours'] !== '')
                                <p class="mt-1 text-ink"><span class="font-bold">Kiedy dzwonić:</span> {{ $p['hours'] }}</p>
                            @endif

                            @include('page.partials.typed._phone-book-number', ['p' => $p, 'hideNumbers' => $hideNumbers, 'link' => $link, 'act' => $act, 'vcard' => $vcard, 'wrap' => 'mt-3'])
                            @if ($p['email'] !== '')
                                <p><a href="mailto:{{ $p['email'] }}" class="{{ $link }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $p['email'] }}</a></p>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @endif
            </section>
        @endforeach

        <p class="mt-8 text-ink" x-show="(q.trim() !== '' || g !== '') && count() === 0" x-cloak>
            Brak osób pasujących do wybranych kryteriów.
        </p>
    @endif
</div>
