{{-- Wspólny nagłówek nowych typów treści: tytuł, lead, przycisk druku i treść główna. Zmienne: $page, $td. --}}
<h1 class="mb-3 text-3xl font-bold text-ink">{{ $page->title }}</h1>
@if (filled($td['lead'] ?? null))
    <p class="mb-4 max-w-3xl text-lg leading-relaxed text-ink">{{ $td['lead'] }}</p>
@endif
<p class="mb-6 no-print">@include('partials.print-button')</p>
@if ($page->content)
    <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
@endif
