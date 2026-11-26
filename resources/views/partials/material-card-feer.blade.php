{{--
    Karta materiału w szablonie FEER — płasko: bez ramek i etykiet na zdjęciu. Typ materiału jest pierwszą linią
    karty: pełny kafelek z ikoną w kolorze typu (nagranie — ink, PDF — brand-dark, scenariusz — akcent #EA8F00 z ikoną ink)
    i nazwa typu tekstem, więc typ nie zależy tylko od koloru. Pod tytułem temat, „Premium” i „Archiwalny” zwykłym tekstem.
    Cała karta to jeden link (tytuł rozciągnięty na kartę) z czytelną nazwą: tytuł + działanie dla czytników ekranu.
    Atrybuty data-* służą filtrom na /materialy. Style .mat-* w educational-materials/partials/styles (bez budowania Tailwinda).
    Kontrast: ink / muted (#4b5563) / brand-dark na gray-50 i gray-100 — wszystko ≥ 4,5:1.
--}}
@php
    $canAccess = ! $material->is_premium
        || ($userCanAccessPremium ?? false)
        || (auth()->user()?->hasFeature("material:{$material->id}") ?? false);
    $locked = ! $canAccess;
    $typeLabel = \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type;
    $typeKey = in_array($material->type, ['video', 'pdf', 'scenariusz'], true) ? $material->type : 'pdf';
    $isVideo = $material->isVideo();
    $categoryLabel = $material->category ? (\App\Models\EducationalMaterial::CATEGORIES[$material->category] ?? $material->category) : null;

    // Jedno działanie karty: dokąd prowadzi link, co mówi wezwanie i co słyszy czytnik ekranu.
    $href = null;
    $newTab = false;
    $preview = false;
    if ($locked) {
        $href = auth()->check() ? route('podcasts.index') : route('login');
        $cta = auth()->check() ? 'Kup dostęp' : 'Zaloguj się, aby otworzyć';
        $srAction = auth()->check() ? 'materiał Premium, kup dostęp' : 'materiał Premium, zaloguj się, aby otworzyć';
    } elseif ($isVideo && $material->video_url) {
        $href = $material->video_url;
        $newTab = true;
        $cta = 'Obejrzyj nagranie';
        $srAction = 'obejrzyj nagranie (otwiera się w nowej karcie)';
    } elseif (! $isVideo && $material->fileUrl) {
        $href = $material->fileUrl;
        $preview = true;
        $cta = $material->type === 'scenariusz' ? 'Otwórz scenariusz' : 'Otwórz PDF';
        $srAction = $material->type === 'scenariusz' ? 'otwórz podgląd scenariusza' : 'otwórz podgląd PDF';
    }
@endphp
<article class="mat-card mat-card--{{ $typeKey }} group" data-material-card data-type="{{ $material->type }}"
         data-group="{{ $material->target_group }}" data-category="{{ $material->category }}"
         data-search="{{ $material->title }} {{ $material->description }}">
    {{-- Podgląd (dekoracyjny — nazwę niesie tytuł) --}}
    <div data-thumb-wrap class="mat-thumb {{ $isVideo ? 'mat-thumb--video' : 'mat-thumb--doc' }} mat-thumb--{{ $typeKey }}" aria-hidden="true">
        @if ($isVideo)
            @if ($material->videoThumbnailUrl())
                <img src="{{ $material->videoThumbnailUrl() }}" alt="" loading="lazy" class="mat-thumb-img">
            @endif
            <span class="mat-play"><i class="fa-solid fa-play"></i></span>
        @else
            <span class="mat-doc-icon"><i class="fa-regular {{ $material->type === 'scenariusz' ? 'fa-clipboard' : 'fa-file-lines' }}"></i></span>
            @if ($material->fileUrl)
                <canvas data-pdf-thumb="{{ $material->fileUrl }}" class="mat-thumb-canvas"></canvas>
            @endif
        @endif
    </div>

    <div class="mat-body">
        <p class="mat-type">
            <span class="mat-type-icon"><i class="fa-solid {{ $material->typeIcon() }}" aria-hidden="true"></i></span>
            {{ $typeLabel }}
        </p>

        <h3 class="mat-title">
            @if ($href)
                <a href="{{ $href }}" class="mat-link"
                   @if ($newTab) target="_blank" rel="noopener" @endif
                   @if ($preview) data-material-preview="{{ $material->title }}" aria-haspopup="dialog" @endif>{{ $material->title }}<span class="sr-only"> — {{ $srAction }}</span></a>
            @else
                {{ $material->title }}
            @endif
        </h3>

        @if ($categoryLabel || $material->is_premium || $material->is_archival)
            <p class="mat-meta">
                @if ($categoryLabel)
                    <span class="mat-meta-item">{{ $categoryLabel }}</span>
                @endif
                @if ($material->is_premium)
                    <span class="mat-meta-item"><i class="fa-solid {{ $locked ? 'fa-lock' : 'fa-lock-open' }}" aria-hidden="true"></i> Premium</span>
                @endif
                @if ($material->is_archival)
                    <span class="mat-meta-item"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archiwalny</span>
                @endif
            </p>
        @endif

        @if ($material->description)
            <p class="mat-desc">{{ $material->description }}</p>
        @endif

        @if ($href)
            <span class="mat-cta" aria-hidden="true">{{ $cta }} <i class="fa-solid fa-arrow-right"></i></span>
        @endif
    </div>
</article>
