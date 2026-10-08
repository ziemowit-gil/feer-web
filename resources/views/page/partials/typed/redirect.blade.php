{{-- Typ „Przekierowanie": informacja o przeniesieniu treści i przycisk do nowego adresu. --}}
@php
    $td = $page->typeData();
    $target = $page->redirectTarget();
    $label = filled($td['redirect_label'] ?? null) ? $td['redirect_label'] : 'Przejdź do nowej strony';
    $external = $target && \Illuminate\Support\Str::startsWith($target, 'http') && ! \Illuminate\Support\Str::contains($target, request()->getHost());
@endphp
<section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="redirect-h">
    <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full border-2 border-brand text-xl text-brand" aria-hidden="true"><i class="fa-solid fa-share"></i></span>
    <h1 id="redirect-h" class="mb-3 text-3xl font-bold text-ink">{{ $page->title }}</h1>
    <p class="mb-6 text-lg text-ink">{{ filled($td['redirect_message'] ?? null) ? $td['redirect_message'] : 'Ta treść została przeniesiona w inne miejsce.' }}</p>
    @if ($target)
        <a href="{{ $target }}" @if ($external) target="_blank" rel="noopener" @endif
            class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand px-6 py-3 font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
            {{ $label }}<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>@if ($external)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
        </a>
    @else
        <p class="text-muted">Adres docelowy nie został jeszcze ustawiony.</p>
    @endif
    @if ($page->content)<div class="prose mx-auto mt-8 text-left">{!! $page->content !!}</div>@endif
</section>
