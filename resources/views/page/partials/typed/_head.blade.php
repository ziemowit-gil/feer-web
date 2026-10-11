{{--
    Wspólny nagłówek typów treści: tytuł, akcent FEER, lead i (opcjonalnie) przycisk druku.
    Treść główną ($page->content) każdy typ wstawia sam — w kolumnie treści.
    Zmienne: $page, $td; $print (domyślnie true) — czy pokazać przycisk druku.
--}}
@php $headFeer = ($siteSettings->site_template ?? 'default') === 'feer'; @endphp
<header class="mb-8 max-w-3xl">
    <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
    @if ($headFeer)<span class="mb-5 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
    @if (filled($td['lead'] ?? null))
        <p class="text-lg leading-relaxed text-ink">{{ $td['lead'] }}</p>
    @endif
    @if (($print ?? true) && ($siteSettings->show_print_button ?? false))
        <p class="mt-5 no-print">@include('partials.print-button')</p>
    @endif
</header>
