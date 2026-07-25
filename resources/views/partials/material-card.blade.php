<article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-brand/40 hover:shadow-lg">
    {{-- Miniatura --}}
    <div data-thumb-wrap class="relative aspect-video overflow-hidden {{ $material->isVideo() ? 'bg-linear-to-br from-brand to-brand-dark' : 'bg-gray-100' }}">
        @if ($material->isVideo())
            @if ($material->videoThumbnailUrl())
                <img src="{{ $material->videoThumbnailUrl() }}" alt="" loading="lazy"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                <span class="absolute inset-0 bg-black/25"></span>
            @endif
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 text-2xl text-brand shadow-lg transition group-hover:scale-110">
                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                </span>
            </span>
        @else
            <span class="absolute inset-0 flex items-center justify-center text-6xl text-brand/30" aria-hidden="true">
                <i class="fa-solid {{ $material->typeIcon() }}"></i>
            </span>
            @if ($material->fileUrl)
                <canvas data-pdf-thumb="{{ $material->fileUrl }}" aria-hidden="true"
                    class="relative z-10 mx-auto h-full w-full object-contain"></canvas>
            @endif
        @endif

        <span class="absolute left-3 top-3 z-20 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-brand shadow-sm">
            <i class="fa-solid {{ $material->typeIcon() }}" aria-hidden="true"></i>
            {{ \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type }}
        </span>

        @if ($material->is_archival)
            <span class="absolute right-3 top-3 z-20 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-amber-800 shadow-sm"
                title="Materiał archiwalny">
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> z dawien dawna
            </span>
        @endif
    </div>

    {{-- Treść --}}
    <div class="flex flex-1 flex-col p-5">
        <h2 class="mb-1 text-lg font-bold text-ink">{{ $material->title }}</h2>
        <p class="mb-4 flex-1 text-sm text-muted">{{ $material->description }}</p>

        @if ($material->isVideo() && $material->video_url)
            <a href="{{ $material->video_url }}" target="_blank" rel="noopener"
                class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-play" aria-hidden="true"></i> Obejrzyj nagranie
            </a>
        @elseif (! $material->isVideo() && $material->fileUrl)
            <div class="mt-auto flex flex-wrap gap-2">
                <a href="{{ $material->fileUrl }}" target="_blank" rel="noopener"
                    class="inline-flex w-fit items-center gap-2 rounded border-2 border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd
                </a>
                <a href="{{ $material->fileUrl }}" target="_blank" rel="noopener" download
                    class="inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz PDF
                </a>
            </div>
        @endif
    </div>
</article>
