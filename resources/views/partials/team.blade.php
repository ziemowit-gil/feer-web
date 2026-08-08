{{-- Zespół — kafelki bez obramowań. Parametry:
     $members          – kolekcja tablic [name, role, photo, bio, facebook, instagram, linkedin, website, substack]
     $personPagesByName – opcjonalna kolekcja Page zaindeksowana po lowercase tytule (do linków „Więcej o…")
--}}
@php
    $members = collect($members)->filter(fn ($m) => ! empty($m['name']))->values();
    $personPagesByName = $personPagesByName ?? collect();
    $socials = [
        'facebook'  => ['fa-brands fa-facebook-f',  'Facebook'],
        'instagram' => ['fa-brands fa-instagram',    'Instagram'],
        'linkedin'  => ['fa-brands fa-linkedin-in',  'LinkedIn'],
        'website'   => ['fa-solid fa-globe',         'Strona WWW'],
        'substack'  => ['fa-solid fa-newspaper',     'Substack'],
    ];
@endphp

@if ($members->isNotEmpty())
    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($members as $m)
            @php
                $initials        = \Illuminate\Support\Str::of($m['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                $nameGenitive    = filled($m['name_genitive'] ?? '') ? $m['name_genitive'] : \Illuminate\Support\Str::of($m['name'])->explode(' ')->first();
                $personPage      = $personPagesByName->get(mb_strtolower(trim($m['name'])));
                $hasSocial  = collect(array_keys($socials))->contains(fn ($k) => ! empty($m[$k]));
            @endphp

            <div class="flex flex-col items-center gap-4 text-center">
                {{-- Zdjęcie / inicjały --}}
                @if (! empty($m['photo']))
                    <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" loading="lazy"
                        class="h-28 w-28 rounded-full object-cover shadow-sm">
                @else
                    <span class="flex h-28 w-28 items-center justify-center rounded-full bg-brand/10 text-2xl font-bold text-brand" aria-hidden="true">{{ $initials }}</span>
                @endif

                {{-- Imię i rola --}}
                <div>
                    <p class="text-base font-bold text-ink">{{ $m['name'] }}</p>
                    @if (! empty($m['role']))
                        <p class="mt-1 text-sm text-muted">{{ $m['role'] }}</p>
                    @endif
                </div>

                {{-- Bio --}}
                @if (! empty($m['bio']))
                    <p class="text-sm leading-relaxed text-muted">{{ $m['bio'] }}</p>
                @endif

                {{-- Social media — te same kolory marki co na stronie osoby --}}
                @if ($hasSocial)
                    <div class="flex items-center justify-center gap-2">
                        @foreach ($socials as $key => [$icon, $label])
                            @if (! empty($m[$key]))
                                <a href="{{ $m[$key] }}" target="_blank" rel="noopener"
                                    aria-label="{{ $label }} — {{ $m['name'] }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-muted transition hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    <i class="{{ $icon }} text-sm" aria-hidden="true"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Link do podstrony osoby --}}
                @if ($personPage)
                    <a href="{{ route('page.show', $personPage) }}"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Więcej o {{ $nameGenitive }}
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
