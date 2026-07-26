{{-- Zespół — lista medialonów (nie karty). Parametr: $members = kolekcja
     tablic [name, role, photo, bio, facebook, instagram, linkedin].
     WCAG: alt = imię, linki social z aria-label, ikony aria-hidden. --}}
@php
    $members = collect($members)->filter(fn ($m) => ! empty($m['name']))->values();
    $socials = [
        'facebook' => ['fa-brands fa-facebook-f', 'Facebook'],
        'instagram' => ['fa-brands fa-instagram', 'Instagram'],
        'linkedin' => ['fa-brands fa-linkedin-in', 'LinkedIn'],
    ];
@endphp

@if ($members->isNotEmpty())
    <div class="mx-auto max-w-4xl divide-y divide-gray-100">
        @foreach ($members as $m)
            @php
                $initials = \Illuminate\Support\Str::of($m['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
            @endphp
            <div class="flex flex-col gap-5 py-8 sm:flex-row sm:items-start">
                <div class="flex-none">
                    @if (! empty($m['photo']))
                        <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" loading="lazy"
                            class="h-28 w-28 rounded-full object-cover sm:h-32 sm:w-32">
                    @else
                        <span class="flex h-28 w-28 items-center justify-center rounded-full bg-brand-light text-2xl font-bold text-brand sm:h-32 sm:w-32" aria-hidden="true">{{ $initials }}</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-ink">{{ $m['name'] }}</h3>
                    @if (! empty($m['role']))
                        <p class="mt-0.5 text-sm font-bold uppercase tracking-wide text-brand">{{ $m['role'] }}</p>
                    @endif
                    @if (! empty($m['bio']))
                        <p class="mt-3 leading-relaxed text-muted">{{ $m['bio'] }}</p>
                    @endif

                    @php $hasSocial = collect(array_keys($socials))->contains(fn ($k) => ! empty($m[$k])); @endphp
                    @if ($hasSocial)
                        <div class="mt-4 flex items-center gap-2">
                            @foreach ($socials as $key => [$icon, $label])
                                @if (! empty($m[$key]))
                                    <a href="{{ $m[$key] }}" target="_blank" rel="noopener"
                                        aria-label="{{ $label }} — {{ $m['name'] }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-light text-brand transition hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                        <i class="{{ $icon }}" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
