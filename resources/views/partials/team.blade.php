{{-- Zespół — medaliony w dwóch kolumnach (nie karty). Parametr: $members =
     kolekcja tablic [name, role, photo, bio, facebook, instagram, linkedin].
     WCAG: alt = imię, linki social z aria-label, ikony aria-hidden. --}}
@php
    $members = collect($members)->filter(fn ($m) => ! empty($m['name']))->values();
    $socials = [
        'facebook' => ['fa-brands fa-facebook-f', 'Facebook'],
        'instagram' => ['fa-brands fa-instagram', 'Instagram'],
        'linkedin' => ['fa-brands fa-linkedin-in', 'LinkedIn'],
        'website' => ['fa-solid fa-globe', 'Strona WWW'],
        'substack' => ['fa-solid fa-newspaper', 'Substack'],
    ];
@endphp

@if ($members->isNotEmpty())
    <div class="grid gap-x-10 gap-y-12 sm:grid-cols-2">
        @foreach ($members as $m)
            @php
                $initials = \Illuminate\Support\Str::of($m['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
            @endphp
            <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-start sm:text-left">
                <div class="flex-none">
                    @if (! empty($m['photo']))
                        <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" loading="lazy"
                            class="h-28 w-28 rounded-full object-cover shadow-md ring-4 ring-brand-light">
                    @else
                        <span class="flex h-28 w-28 items-center justify-center rounded-full bg-brand text-2xl font-bold text-white shadow-md ring-4 ring-brand-light" aria-hidden="true">{{ $initials }}</span>
                    @endif
                </div>

                <div class="min-w-0 sm:pt-1">
                    <h3 class="text-lg font-bold text-ink">{{ $m['name'] }}</h3>
                    @if (! empty($m['role']))
                        <p class="mt-1 inline-block rounded-full bg-brand-light px-3 py-0.5 text-xs font-bold uppercase tracking-wide text-brand">{{ $m['role'] }}</p>
                    @endif
                    @if (! empty($m['bio']))
                        <p class="mt-3 text-sm leading-relaxed text-muted">{{ $m['bio'] }}</p>
                    @endif

                    @php $hasSocial = collect(array_keys($socials))->contains(fn ($k) => ! empty($m[$k])); @endphp
                    @if ($hasSocial)
                        <div class="mt-4 flex items-center justify-center gap-2 sm:justify-start">
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
