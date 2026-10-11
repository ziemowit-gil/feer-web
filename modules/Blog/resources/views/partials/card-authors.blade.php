{{--
    Autorzy na karcie wpisu (siatka i wpis wyróżniony): gdy któryś profil ma zdjęcie —
    kwadratowa miniatura 32 px (rounded-md) przy imieniu, alt="" bo imię stoi obok;
    w przeciwnym razie zwykła linia z partials.authors-inline.
    Zmienne: $article, opcjonalnie $class (klasy akapitu).
--}}
@php
    $profiles = $article->authorProfiles();
    $withPhoto = $profiles->contains(fn ($p) => $p->photoUrl());
    $authorLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($withPhoto)
    <p class="{{ $class ?? 'mt-2' }} flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink">
        <span class="sr-only">{{ $profiles->count() > 1 ? 'Autorzy:' : 'Autor:' }} </span>
        @foreach ($profiles as $p)
            <span class="inline-flex items-center gap-2">
                @if ($photo = $p->photoUrl())
                    <img src="{{ $photo }}" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 shrink-0 rounded-md object-cover">
                @endif
                <a href="{{ $p->url() }}" class="{{ $authorLink }}">{{ $p->name }}</a>{{ $loop->last ? '' : ',' }}
            </span>
        @endforeach
    </p>
@elseif ($profiles->isNotEmpty() || filled($article->author_name))
    <p class="{{ $class ?? 'mt-2' }} text-sm text-ink"><span class="sr-only">Autor: </span>@include('blog::partials.authors-inline', ['article' => $article])</p>
@endif
