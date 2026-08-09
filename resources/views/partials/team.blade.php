{{-- Zespół — pozioma lista.
     $members – kolekcja Page (about_person, opublikowane, posortowane)
--}}
@php
    $members = collect($members)->filter(fn ($m) => filled($m->title))->values();
@endphp

@if ($members->isNotEmpty())
    <ul class="grid gap-6 sm:grid-cols-2" role="list">
        @foreach ($members as $m)
            @php
                $initials     = \Illuminate\Support\Str::of($m->title)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                $nameGenitive = filled($m->person_name_genitive) ? $m->person_name_genitive : \Illuminate\Support\Str::of($m->title)->explode(' ')->first();
                $photo        = $m->content_image;
            @endphp

            <li class="flex items-start gap-5">
                {{-- Avatar --}}
                @if ($photo)
                    <img src="{{ $photo }}" alt="{{ $m->title }}" loading="lazy"
                        class="h-36 w-36 shrink-0 rounded-full object-cover">
                @else
                    <span class="flex h-36 w-36 shrink-0 items-center justify-center rounded-full bg-brand/10 text-4xl font-bold text-brand" aria-hidden="true">{{ $initials }}</span>
                @endif

                {{-- Tekst --}}
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-ink">{{ $m->title }}</p>
                    @if (filled($m->person_role))
                        <p class="truncate text-sm text-muted">{{ $m->person_role }}</p>
                    @endif
                    @if (filled($m->person_bio))
                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $m->person_bio }}</p>
                    @endif
                    <a href="{{ $m->publicUrl() }}"
                        class="mt-2 inline-flex items-center gap-1.5 rounded bg-brand px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Więcej o {{ $nameGenitive }}
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
@endif
