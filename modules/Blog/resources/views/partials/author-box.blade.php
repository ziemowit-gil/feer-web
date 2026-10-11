{{--
    Notka o autorze: zdjęcie, imię (odnośnik do strony autora), rola, nota,
    linki i — tylko gdy autor na to pozwolił — e-mail. Płasko, szare tło.
    Zmienne: $author (BlogAuthor), opcjonalnie $headingLevel (3), $linkName (true).
--}}
@php
    $hl = $headingLevel ?? 3;
    $linkName = $linkName ?? true;
    $photo = $author->photoUrl();
    $abLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<div class="flex flex-col gap-4 rounded-md bg-gray-50 p-5 sm:flex-row sm:items-start">
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $author->photoAlt() }}" class="h-24 w-24 shrink-0 rounded-md object-cover" loading="lazy">
    @endif
    <div class="min-w-0">
        <h{{ $hl }} class="text-lg font-bold text-ink">
            @if ($linkName)
                <a href="{{ $author->url() }}" class="hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $author->name }}</a>
            @else
                {{ $author->name }}
            @endif
        </h{{ $hl }}>
        @if ($author->role)
            <p class="text-sm text-ink">{{ $author->role }}</p>
        @endif
        @if ($author->bio)
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $author->bio }}</p>
        @endif
        @php $links = $author->linkList(); @endphp
        @if ($links || ($author->show_email && $author->email))
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach ($links as $l)
                    <li><a href="{{ $l['url'] }}" class="{{ $abLink }}" rel="noopener me" target="_blank">{{ $l['label'] }}<span class="sr-only"> — {{ $author->name }} (otwiera się w nowej karcie)</span></a></li>
                @endforeach
                @if ($author->show_email && $author->email)
                    <li><a href="mailto:{{ $author->email }}" class="{{ $abLink }}"><i class="fa-solid fa-envelope mr-1" aria-hidden="true"></i>{{ $author->email }}</a></li>
                @endif
            </ul>
        @endif
        @if ($linkName)
            <p class="mt-3 text-sm"><a href="{{ $author->url() }}" class="{{ $abLink }}">Wszystkie wpisy autora<span class="sr-only">: {{ $author->name }}</span></a></p>
        @endif
    </div>
</div>
