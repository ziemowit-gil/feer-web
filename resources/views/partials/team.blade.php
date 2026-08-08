{{-- Zespół — pozioma lista. Parametry:
     $members          – kolekcja tablic [name, name_genitive, role]
     $personPagesByName – opcjonalna kolekcja Page zaindeksowana po lowercase tytule
--}}
@php
    $members = collect($members)->filter(fn ($m) => ! empty($m['name']))->values();
    $personPagesByName = $personPagesByName ?? collect();
@endphp

@if ($members->isNotEmpty())
    <ul class="grid gap-6 sm:grid-cols-2" role="list">
        @foreach ($members as $m)
            @php
                $initials     = \Illuminate\Support\Str::of($m['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                $nameGenitive = filled($m['name_genitive'] ?? '') ? $m['name_genitive'] : \Illuminate\Support\Str::of($m['name'])->explode(' ')->first();
                $personPage   = $personPagesByName->get(mb_strtolower(trim($m['name'])));
                $photo        = $personPage?->content_image;
            @endphp

            <li class="flex items-center gap-5">
                {{-- Avatar: zdjęcie z podstrony osoby lub inicjały --}}
                @if ($photo)
                    <img src="{{ $photo }}" alt="{{ $m['name'] }}" loading="lazy"
                        class="h-20 w-20 shrink-0 rounded-full object-cover">
                @else
                    <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-brand/10 text-2xl font-bold text-brand" aria-hidden="true">{{ $initials }}</span>
                @endif

                {{-- Tekst --}}
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-ink">{{ $m['name'] }}</p>
                    @if (! empty($m['role']))
                        <p class="truncate text-sm text-muted">{{ $m['role'] }}</p>
                    @endif
                    @if (! empty($m['bio']))
                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $m['bio'] }}</p>
                    @endif
                    @if ($personPage)
                        <a href="{{ $personPage->publicUrl() }}"
                            class="mt-2 inline-flex items-center gap-1.5 rounded bg-brand px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Więcej o {{ $nameGenitive }}
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
