{{--
    Karta „Podstrony kontaktu” w edycji strony typu „Kontakt”: które automatyczne podstrony (/kontakt/…) mają dane, jakie
    podstrony dodała redakcja i skrót do dodania książki telefonicznej. Tylko informacja — pola kontaktu są w sekcji powyżej.
--}}
@php
    $csSections = \Modules\Contact\Support\ContactSections::available(
        $siteSettings,
        \Modules\Contact\Support\ContactSections::hasMeetings($siteSettings),
        \Modules\Contact\Support\ContactSections::hasShipping($siteSettings),
    );
    $csAll = [
        'spotkania' => 'Spotkania', 'przesylki' => 'Przesyłki', 'rachunki' => 'Numery rachunków',
        'oddzialy' => 'Oddziały', 'dane-rejestrowe' => 'Dane rejestrowe',
    ];
    $csChildren = $page->exists ? $page->children()->get() : collect();
    $csHasBook = $csChildren->contains(fn ($c) => in_array($c->type, \Modules\Contact\Support\ContactSections::BOOK_TYPES, true));
@endphp
<div class="space-y-3 border-t border-gray-100 pt-5">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Podstrony kontaktu</p>
    <p class="text-xs text-muted">W menu bocznym strony kontaktu (szablon FEER) widać podstrony z ustawień oraz podstrony dodane poniżej tej strony. Pola podstron uzupełniasz w Ustawienia → Kontakt.</p>
    <ul class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2" role="list">
        @foreach ($csAll as $slug => $label)
            <li class="flex items-center gap-2">
                <i class="fa-solid {{ array_key_exists($slug, $csSections) ? 'fa-circle-check text-green-700' : 'fa-circle-minus text-muted' }}" aria-hidden="true"></i>
                <span class="{{ array_key_exists($slug, $csSections) ? 'font-bold text-ink' : 'text-muted' }}">{{ $label }}</span>
                <span class="text-xs text-muted">{{ array_key_exists($slug, $csSections) ? 'jest' : 'brak danych' }}</span>
            </li>
        @endforeach
    </ul>
    @if ($page->exists)
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-1 text-sm">
            @if ($csChildren->isNotEmpty())
                <span class="text-muted">Podstrony: {{ $csChildren->pluck('title')->implode(', ') }}</span>
            @endif
            @unless ($csHasBook)
                <a href="{{ route('admin.podstrony.create', ['type' => 'contact_book', 'parent_id' => $page->id]) }}"
                   class="inline-flex min-h-9 items-center gap-2 rounded border border-brand-dark px-3 py-1.5 font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-address-book" aria-hidden="true"></i> Dodaj książkę telefoniczną jako podstronę
                </a>
            @endunless
        </div>
    @endif
</div>
