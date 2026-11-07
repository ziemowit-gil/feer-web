{{--
    Typ „Kontakt — książka”: nagłówek strony, treść i lista osób z modułu „Książka telefoniczna” (tabela phone_book_entries),
    opcjonalnie tylko jeden dział (type_data.group). Wygląd listy: partials/phone-book/list (tabele albo karty, filtr, szukanie,
    kopiowanie, vCard, ukrywanie numerów). Gdy osób w tabeli jeszcze nie ma, a strona ma stare dane (type_data.contacts —
    przed przeniesieniem albo po cofnięciu migracji), pokazujemy je, żeby strona nie była pusta.
--}}
@php
    $td = $page->typeData();
    $bookContacts = \App\Models\PhoneBookEntry::listForCurrentSite();
    if ($bookContacts === [] && ! empty($td['contacts'])) {
        $bookContacts = $td['contacts'];
    }
    if (filled($td['group'] ?? null)) {
        $bookContacts = array_values(array_filter($bookContacts, fn ($c) => mb_strtolower(trim((string) ($c['group'] ?? ''))) === mb_strtolower(trim((string) $td['group']))));
    }
@endphp

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="contact_book">
    @include('page.partials.typed._head', ['print' => true])

    @if ($page->content)
        <div class="prose mb-8 max-w-3xl text-ink">@shortcodes($page->content)</div>
    @endif

    @include('partials.phone-book.list', ['contacts' => $bookContacts, 'layout' => $td['layout'] ?? 'table', 'sort' => $td['sort'] ?? 'manual', 'hide' => ! empty($td['hide_numbers'])])

    @include('partials.attachments-list', ['attachments' => $page->attachments])
    @include('partials.page-section-nav', ['page' => $page])
</section>
