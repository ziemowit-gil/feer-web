{{--
    Typ „Książka telefoniczna": nagłówek strony, treść i lista osób z pól `type_data` (lista: partials/phone-book/list).
--}}
@php $td = $page->typeData(); @endphp

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="phone_book">
    @include('page.partials.typed._head', ['print' => true])

    @if ($page->content)
        <div class="prose mb-8 max-w-3xl text-ink">@shortcodes($page->content)</div>
    @endif

    @include('partials.phone-book.list', ['contacts' => $td['contacts'] ?? [], 'layout' => $td['layout'] ?? 'table', 'sort' => $td['sort'] ?? 'manual', 'hide' => ! empty($td['hide_numbers'])])

    @include('partials.attachments-list', ['attachments' => $page->attachments])
    @include('partials.page-section-nav', ['page' => $page])
</section>
