{{-- Zespół — siatka kart, w stylu sekcji "Nasze projekty" / "Nasza sieć"
     z szablonów ngo/federacja (rounded-2xl, ring, stretched-link).
     $members – kolekcja Page (about_person, opublikowane, posortowane)
--}}
@php
    $members = collect($members)->filter(fn ($m) => filled($m->title))->values();
@endphp

@if ($members->isNotEmpty())
    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
        @foreach ($members as $m)
            @php
                $initials = \Illuminate\Support\Str::of($m->title)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                $photo    = $m->content_image;
            @endphp

            <li class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                {{-- Zdjęcie --}}
                @if ($photo)
                    <img src="{{ $photo }}" alt="{{ $m->title }}" loading="lazy"
                        class="aspect-square w-full object-cover">
                @else
                    <div class="flex aspect-square w-full items-center justify-center bg-brand/10 text-4xl font-bold text-brand" aria-hidden="true">
                        {{ $initials }}
                    </div>
                @endif

                {{-- Tekst --}}
                <div class="flex flex-1 flex-col gap-1 p-5 text-center">
                    <p class="text-base font-extrabold text-ink group-hover:text-brand">
                        <a href="{{ $m->publicUrl() }}" class="stretched-link">{{ $m->title }}</a>
                    </p>
                    @if (filled($m->person_role))
                        <p class="text-sm text-muted">{{ $m->person_role }}</p>
                    @endif
                    @if (filled($m->person_bio))
                        <p class="mt-1 text-sm leading-relaxed text-muted line-clamp-3">{{ $m->person_bio }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
