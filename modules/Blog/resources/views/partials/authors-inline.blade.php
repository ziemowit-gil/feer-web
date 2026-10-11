{{--
    Autorzy wpisu w jednej linii: imiona jako odnośniki do stron autorów,
    a dla starych wpisów bez profilu — zwykły tekst z `author_name`.
    Zmienne: $article, opcjonalnie $linkClass.
--}}
@php
    $profiles = $article->authorProfiles();
    $linkClass = $linkClass ?? 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($profiles->isNotEmpty())
    @foreach ($profiles as $p)
        <a href="{{ $p->url() }}" class="{{ $linkClass }}">{{ $p->name }}</a>{{ $loop->last ? '' : ($loop->remaining === 1 ? ' i' : ',') }}
    @endforeach
@elseif (filled($article->author_name))
    <span class="font-bold text-ink">{{ $article->author_name }}</span>
@endif
