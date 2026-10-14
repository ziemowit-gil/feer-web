{{-- Tytuł i wstęp portalu (akapity oddzielone pustą linią). Expects: $title, $introParagraphs, $bar. --}}
<header>
    <h1 class="text-3xl font-bold leading-tight text-ink md:text-4xl">{{ $title }}</h1>
    <span class="{{ $bar }}" aria-hidden="true"></span>
    @if ($introParagraphs)
        <div class="mt-5 max-w-3xl space-y-4 text-lg leading-relaxed text-ink">
            @foreach ($introParagraphs as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>
    @endif
</header>
