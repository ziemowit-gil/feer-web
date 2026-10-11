{{--
    Typ „Przekierowanie": informacja o przeniesieniu treści i przycisk do nowego adresu.
    Płasko: jedna kolumna, ikona bez tła i obramowania, tylko kolory firmowe.
--}}
@php
    $feer = ($siteSettings->site_template ?? 'default') === 'feer';
    $td = $page->typeData();
    $target = $page->redirectTarget();
    $label = filled($td['redirect_label'] ?? null) ? $td['redirect_label'] : 'Przejdź do nowej strony';
    $external = $target && \Illuminate\Support\Str::startsWith($target, 'http') && ! \Illuminate\Support\Str::contains($target, request()->getHost());
@endphp
<section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="redirect-h" data-page-type="redirect">
    <i class="fa-solid fa-share mb-5 block text-4xl text-brand-dark" aria-hidden="true"></i>
    <h1 id="redirect-h" class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
    @if ($feer)<span class="mx-auto mb-5 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
    <p class="mb-6 text-lg text-ink">{{ filled($td['redirect_message'] ?? null) ? $td['redirect_message'] : 'Ta treść została przeniesiona w inne miejsce.' }}</p>
    @if ($target)
        <a href="{{ $target }}" @if ($external) target="_blank" rel="noopener" @endif
            class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand-dark px-6 py-3 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            {{ $label }}<i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>@if ($external)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
        </a>
    @else
        <p class="text-muted">Adres docelowy nie został jeszcze ustawiony.</p>
    @endif
    @if ($page->content)<div class="prose mx-auto mt-8 text-left text-ink">{!! $page->content !!}</div>@endif
</section>
