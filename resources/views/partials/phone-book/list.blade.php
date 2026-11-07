{{--
    Lista książki telefonicznej (wspólna dla modułu „Książka telefoniczna”, typu „Kontakt — książka” i dawnego typu):
    kto, numer komórkowy, w jakiej sprawie i kiedy dzwonić.

    Układ: jeden panel na górze (numer kontaktowy, wyszukiwarka z czyszczeniem, filtr działów, licznik wyników),
    a pod nim działy z osobami. $layout = table → lista (osoba w wierszu: dane po lewej, numer po prawej),
    $layout = cards → siatka kart. Numery bez linków „zadzwoń” (książka służy do rozpoznania, kto dzwoni),
    opcjonalnie ukryte za przyciskiem; „Zapisz w telefonie” tworzy plik .vcf w przeglądarce.
    Zmienne: $contacts (tablice: group, name, role, phone, email, topic, hours), $layout (table|cards), $sort (manual|alpha), $hide (bool).

    Style w bloku <style> poniżej (klasy pb-*), żeby wygląd nie zależał od przebudowy Tailwinda na serwerze.
    Płasko, kolory marki; tekst ink (#1D1D1A) i #374151 na #F3F4F6 ≥ 7:1, cele dotyku ≥ 44 px, widoczny fokus.
--}}
@php
    $alpha = ($sort ?? 'manual') === 'alpha';
    $hideNumbers = ! empty($hide);
    $asRows = ($layout ?? 'table') !== 'cards';
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
    if ($alpha) {
        $people = $people->sortBy(fn ($p) => mb_strtolower($p['name'] ?: $p['phone']), SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
    $groups = $people->groupBy('group');
    if ($alpha) {
        $groups = $groups->sortBy(fn ($list, $name) => $name === '' ? '' : mb_strtolower($name), SORT_NATURAL | SORT_FLAG_CASE);
    }
    $groupNames = $groups->keys()->filter(fn ($g) => $g !== '')->values();
    $osob = fn (int $n) => $n === 1 ? 'osoba' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? 'osoby' : 'osób');

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

    $link = 'pb-link';
    $act = 'pb-act';
    $mainPhone = trim((string) ($siteSettings->contact_phone ?? ''));
@endphp

@once
<style>
    .pb-panel { background: #f3f4f6; border-radius: .75rem; padding: 1.25rem; color: #1d1d1a; }
    @media (min-width: 48rem) { .pb-panel { padding: 1.5rem 1.75rem; } }
    .pb-main { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; }
    .pb-main-ico { display: flex; width: 2.75rem; height: 2.75rem; flex: none; align-items: center; justify-content: center; border-radius: 999px; background: #fff; color: var(--color-brand-dark, #1752bf); }
    .pb-label { font-size: .8125rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .pb-main-num { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .pb-main-text { margin-top: .5rem; max-width: 46rem; line-height: 1.6; }
    .pb-tools { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #d1d5db; }
    .pb-search { position: relative; max-width: 40rem; margin-top: .5rem; }
    .pb-search input { width: 100%; min-height: 3rem; border: 2px solid #6b7280; border-radius: .5rem; background: #fff; padding: 0 3rem 0 2.75rem; font-size: 1rem; color: #1d1d1a; }
    .pb-search input::placeholder { color: #4b5563; opacity: 1; }
    .pb-search input:focus { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; border-color: var(--color-brand-dark, #1752bf); }
    .pb-search .pb-search-ico { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .pb-clear { position: absolute; right: .125rem; top: 50%; transform: translateY(-50%); display: flex; width: 2.75rem; height: 2.75rem; align-items: center; justify-content: center; border-radius: .375rem; background: transparent; color: #1d1d1a; }
    .pb-clear:hover { background: #e5e7eb; }
    .pb-clear:focus-visible, .pb-chip:focus-visible, .pb-act:focus-visible, .pb-link:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; }
    .pb-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
    .pb-chip { min-height: 2.75rem; border-radius: 999px; padding: 0 1rem; font-weight: 700; background: #fff; color: #1d1d1a; }
    .pb-chip:hover { background: #e5e7eb; }
    .pb-chip[aria-pressed="true"] { background: var(--color-brand-dark, #1752bf); color: #fff; }
    .pb-count { margin-top: .875rem; font-weight: 700; }
    .pb-group { margin-top: 2.5rem; }
    .pb-group-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .5rem; font-size: 1.375rem; font-weight: 800; color: #1d1d1a; }
    .pb-group-n { font-size: 1rem; font-weight: 600; color: #374151; }
    .pb-bar { display: block; width: 2.5rem; height: .25rem; margin-top: .5rem; background: var(--color-brand, #1e6dff); }
    .pb-list { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: .75rem; }
    .pb-list--cards { grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); }
    .pb-person { display: grid; gap: .75rem 2rem; border-radius: .75rem; background: #f3f4f6; padding: 1.125rem 1.25rem; color: #1d1d1a; }
    @media (min-width: 48rem) { .pb-list--rows .pb-person { grid-template-columns: minmax(0, 1fr) 17rem; align-items: start; } }
    .pb-name { font-size: 1.125rem; font-weight: 800; line-height: 1.3; }
    .pb-role { color: #374151; }
    .pb-meta { margin: .625rem 0 0; display: grid; gap: .25rem; line-height: 1.55; }
    .pb-meta div { display: flex; flex-wrap: wrap; gap: 0 .375rem; }
    .pb-meta dt { font-weight: 700; }
    .pb-meta dd { margin: 0; }
    .pb-phone .text-xl { font-size: 1.25rem; }
    .pb-link { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 4px; word-break: break-all; }
    .pb-link:hover { text-decoration: none; }
    .pb-act { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; border-radius: .375rem; background: #fff; padding: 0 .875rem; font-size: .875rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); }
    .pb-act:hover { background: var(--color-brand-light, #e8f0ff); }
    .pb-empty { margin-top: 2rem; border-radius: .75rem; background: #f3f4f6; padding: 1.25rem; }
    @media print { .pb-tools, .pb-act { display: none !important; } .pb-person { break-inside: avoid; } }
</style>
@endonce

<div data-phone-book class="pb"
    x-data="{
        q: '', g: '', total: {{ $people->count() }},
        match(el) {
            if (this.g !== '' && el.dataset.group !== this.g) return false;
            // Tekst bez znaków innych niż litery, cyfry i spacje; numer bez spacji i myślników („601350487” znajdzie „601 350 487”).
            const t = this.q.toLowerCase().replace(/[^\p{L}\p{N}\s]+/gu, ' ').replace(/\s+/g, ' ').trim();
            if (t === '') return true;
            const d = t.replace(/\D/g, '');
            const onlyDigits = ! /\p{L}/u.test(t);
            if (onlyDigits) return d.length >= 2 && el.dataset.digits.includes(d);
            return t.split(' ').every((w) => el.dataset.person.includes(w));
        },
        count() { return [...this.$el.querySelectorAll('[data-person]')].filter((el) => this.match(el)).length; },
        osob(n) { return n === 1 ? 'osoba' : ((n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 12 || n % 100 > 14)) ? 'osoby' : 'osób'); },
        summary() {
            const n = this.count();
            return (this.q.replace(/[^\p{L}\p{N}]+/gu, '') || this.g) ? ('Znaleziono: ' + n + ' z ' + this.total) : ('W książce: ' + this.total + ' ' + this.osob(this.total));
        },
        save(card, file) {
            const url = URL.createObjectURL(new Blob([card], { type: 'text/vcard;charset=utf-8' }));
            const a = document.createElement('a'); a.href = url; a.download = file; document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }
    }">

    @if ($mainPhone !== '' || $people->isNotEmpty())
        <div class="pb-panel">
            @if ($mainPhone !== '')
                <div role="note" aria-label="Numer kontaktowy">
                    <div class="pb-main">
                        <span class="pb-main-ico" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                        <div>
                            <p class="pb-label">Numer kontaktowy</p>
                            <p class="pb-main-num">{{ $mainPhone }}</p>
                        </div>
                    </div>
                    <p class="pb-main-text">Informacje o naszej działalności. Z tą listą sprawdzisz, kto do Ciebie dzwonił. Żeby coś u nas załatwić, dzwoń na numer kontaktowy.</p>
                </div>
            @endif

            @if ($people->isNotEmpty())
                <div class="{{ $mainPhone !== '' ? 'pb-tools' : '' }} no-print">
                    <label for="phonebook-q" class="font-bold">Znajdź osobę, sprawę albo numer</label>
                    <div class="pb-search">
                        <i class="fa-solid fa-magnifying-glass pb-search-ico" aria-hidden="true"></i>
                        <input id="phonebook-q" x-ref="q" type="search" x-model.debounce.150ms="q" placeholder="np. faktury, zapisy, imię albo numer" autocomplete="off">
                        <button type="button" class="pb-clear" x-show="q !== ''" x-cloak @click="q = ''; $refs.q.focus()" aria-label="Wyczyść wyszukiwanie"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>

                    @if ($groupNames->count() > 1)
                        <div class="pb-chips" role="group" aria-label="Filtr działów">
                            <button type="button" class="pb-chip" @click="g = ''" :aria-pressed="(g === '').toString()" aria-pressed="true">Wszystkie</button>
                            @foreach ($groupNames as $gn)
                                <button type="button" class="pb-chip" @click="g = @js($gn)" :aria-pressed="(g === @js($gn)).toString()" aria-pressed="false">{{ $gn }}</button>
                            @endforeach
                        </div>
                    @endif

                    <p class="pb-count" aria-live="polite" x-text="summary()">W książce: {{ $people->count() }} {{ $osob($people->count()) }}</p>
                </div>
            @endif
        </div>
    @endif

    @if ($people->isEmpty())
        <p class="pb-empty">Książka telefoniczna nie ma jeszcze wpisów. Dodaj osoby w panelu (Komunikacja → Książka telefoniczna).</p>
    @else
        @foreach ($groups as $groupName => $list)
            <section class="pb-group" @if ($groupName !== '') aria-labelledby="phonebook-group-{{ $loop->index }}" @else aria-label="Osoby" @endif
                     x-show="[...$el.querySelectorAll('[data-person]')].some((el) => match(el))">
                @if ($groupName !== '')
                    <h2 id="phonebook-group-{{ $loop->index }}" class="pb-group-h">{{ $groupName }} <span class="pb-group-n">({{ $list->count() }})</span></h2>
                    <span class="pb-bar" aria-hidden="true"></span>
                @endif
                <ul role="list" class="pb-list {{ $asRows ? 'pb-list--rows' : 'pb-list--cards' }}">
                    @foreach ($list as $p)
                        <li class="pb-person" data-group="{{ $p['group'] }}" data-digits="{{ preg_replace('/\D/', '', $p['phone']) }}"
                            data-person="{{ mb_strtolower($p['name'].' '.$p['role'].' '.$p['topic'].' '.$p['phone'].' '.$p['group'].' '.$p['hours'].' '.$p['email']) }}" x-show="match($el)">
                            <div class="min-w-0">
                                <p class="pb-name">{{ $p['name'] !== '' ? $p['name'] : $p['phone'] }}</p>
                                @if ($p['role'] !== '')<p class="pb-role">{{ $p['role'] }}</p>@endif
                                @if ($p['topic'] !== '' || $p['hours'] !== '' || $p['email'] !== '')
                                    <dl class="pb-meta">
                                        @if ($p['topic'] !== '')<div><dt>W sprawie:</dt><dd>{{ $p['topic'] }}</dd></div>@endif
                                        @if ($p['hours'] !== '')<div><dt>Kiedy dzwonić:</dt><dd>{{ $p['hours'] }}</dd></div>@endif
                                        @if ($p['email'] !== '')<div><dt class="sr-only">E-mail:</dt><dd><a href="mailto:{{ $p['email'] }}" class="pb-link"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $p['email'] }}</a></dd></div>@endif
                                    </dl>
                                @endif
                            </div>
                            <div class="pb-phone">
                                @include('page.partials.typed._phone-book-number', ['p' => $p, 'hideNumbers' => $hideNumbers, 'link' => $link, 'act' => $act, 'vcard' => $vcard, 'wrap' => ''])
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <p class="pb-empty" x-show="(q.replace(/[^\p{L}\p{N}]+/gu, '') !== '' || g !== '') && count() === 0" x-cloak role="status">
            Nikogo nie znaleziono. Sprawdź pisownię albo wpisz część numeru, np. „601 350”.
        </p>
    @endif
</div>
