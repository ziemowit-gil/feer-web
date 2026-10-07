{{--
    Karta materiału w szablonie FEER: duży podgląd (miniatura nagrania / pierwsza strona PDF), znacznik typu, tytuł,
    opis i wyraźne akcje. Płasko — jasnoszare tło, bez ramek i cieni; po najechaniu podgląd lekko się powiększa.
    Kontrast: znaczniki ink na bieli, napis na zablokowanym materiale biały na ciemnym (#1D1D1A, ≥ 16:1).
--}}
@php
    $canAccess = ! $material->is_premium
        || ($userCanAccessPremium ?? false)
        || (auth()->user()?->hasFeature("material:{$material->id}") ?? false);
    $locked = ! $canAccess;
    $typeLabel = \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type;
    $btnPrimary = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    $btnGhost = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp
<article class="feer-card group flex h-full flex-col overflow-hidden rounded-lg bg-gray-50 hover:bg-gray-100">
    {{-- Podgląd --}}
    <div data-thumb-wrap class="relative aspect-video overflow-hidden {{ $material->isVideo() ? 'bg-ink' : 'bg-gray-200' }}">
        @if ($material->isVideo())
            @if ($material->videoThumbnailUrl())
                <img src="{{ $material->videoThumbnailUrl() }}" alt="" loading="lazy"
                     class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
            @endif
            <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                <span class="flex h-14 w-14 items-center justify-center rounded-md bg-white text-xl text-ink transition group-hover:bg-brand group-hover:text-white">
                    <i class="fa-solid fa-play"></i>
                </span>
            </span>
        @else
            <span class="absolute inset-0 flex items-center justify-center text-6xl text-gray-400" aria-hidden="true">
                <i class="fa-solid {{ $material->typeIcon() }}"></i>
            </span>
            @if ($material->fileUrl)
                <canvas data-pdf-thumb="{{ $material->fileUrl }}" aria-hidden="true"
                        class="relative z-10 mx-auto h-full w-full object-contain transition duration-500 group-hover:scale-[1.03]"></canvas>
            @endif
        @endif

        <span class="absolute left-3 top-3 z-20 inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-ink">
            <i class="fa-solid {{ $material->typeIcon() }}" aria-hidden="true"></i> {{ $typeLabel }}
        </span>
        @if ($material->is_archival)
            <span class="absolute right-3 top-3 z-20 rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-amber-900">Archiwalny</span>
        @elseif ($material->is_premium)
            <span class="absolute right-3 top-3 z-20 rounded-md bg-ink px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-white">Premium</span>
        @endif

        @if ($locked)
            <span class="absolute inset-0 z-10 flex items-center justify-center bg-ink/70 text-white" aria-hidden="true"><i class="fa-solid fa-lock text-3xl"></i></span>
        @endif
    </div>

    {{-- Treść --}}
    <div class="flex flex-1 flex-col p-5">
        @if ($material->category)
            <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ \App\Models\EducationalMaterial::CATEGORIES[$material->category] ?? $material->category }}</p>
        @endif
        <h3 class="mt-1 text-lg font-bold leading-snug text-ink">{{ $material->title }}</h3>
        @if ($material->description)
            <p class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-muted">{{ $material->description }}</p>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($locked)
                <p class="w-full text-sm font-medium text-ink">Dostępny w subskrypcji Premium.</p>
                @auth
                    <a href="{{ route('podcasts.index') }}" class="{{ $btnPrimary }}">Kup dostęp</a>
                @else
                    <a href="{{ route('login') }}" class="{{ $btnPrimary }}">Zaloguj się</a>
                @endauth
            @elseif ($material->isVideo() && $material->video_url)
                <a href="{{ $material->video_url }}" target="_blank" rel="noopener" class="{{ $btnPrimary }}">
                    <i class="fa-solid fa-play" aria-hidden="true"></i> Obejrzyj nagranie<span class="sr-only"> — {{ $material->title }} (otwiera się w nowej karcie)</span>
                </a>
            @elseif (! $material->isVideo() && $material->fileUrl)
                <a href="{{ $material->fileUrl }}" target="_blank" rel="noopener" download class="{{ $btnPrimary }}">
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz PDF<span class="sr-only"> — {{ $material->title }}</span>
                </a>
                <a href="{{ $material->fileUrl }}" target="_blank" rel="noopener" class="{{ $btnGhost }}">
                    Podgląd<span class="sr-only"> — {{ $material->title }} (otwiera się w nowej karcie)</span>
                </a>
            @endif
        </div>
    </div>
</article>
