{{-- Tytuł, lead (wyróżnione zdanie z panelu) i wstęp portalu (akapity oddzielone pustą linią). Expects: $title, $category, $introParagraphs, $bar. --}}
<header>
    <h1 class="text-3xl font-bold leading-tight text-ink md:text-4xl">{{ $title }}</h1>
    <span class="{{ $bar }}" aria-hidden="true"></span>
    @if ($lead = $category->portalOption('lead'))
        <p class="mt-5 max-w-3xl text-xl font-semibold leading-snug text-ink md:text-2xl">{{ $lead }}</p>
    @endif
    @if ($introParagraphs)
        <div class="{{ $lead ? 'mt-4' : 'mt-5' }} max-w-3xl space-y-4 text-lg leading-relaxed text-ink">
            @foreach ($introParagraphs as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>
    @endif
</header>
