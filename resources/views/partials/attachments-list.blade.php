@php
    $attachments ??= collect();
    // Strona może ukryć listę „Pliki do pobrania” (panel: Lista plików na stronie); pliki zostają w panelu.
    if (isset($page) && $page instanceof \App\Models\Page && ! empty(($page->layout_data ?? [])['hide_attachments'])) {
        $attachments = collect();
    }
@endphp

@if ($attachments->isNotEmpty())
    @include('partials.attachments-css')
    @php $dlId = 'dl-h-'.\Illuminate\Support\Str::random(6); @endphp
    <section class="dl-frame" aria-labelledby="{{ $dlId }}">
        <h2 id="{{ $dlId }}" class="dl-h">Pliki do pobrania</h2>
        <span class="dl-accent" aria-hidden="true"></span>
        <ul role="list" class="dl-grid">
            @foreach ($attachments as $attachment)
                @php
                    $ext = strtolower($attachment->file_extension ?? '');
                    $fileIcon = match (true) {
                        $ext === 'pdf'                                    => 'fa-file-pdf',
                        in_array($ext, ['doc', 'docx', 'odt', 'rtf'])     => 'fa-file-word',
                        in_array($ext, ['xls', 'xlsx', 'ods', 'csv'])     => 'fa-file-excel',
                        in_array($ext, ['ppt', 'pptx', 'odp'])            => 'fa-file-powerpoint',
                        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz']) => 'fa-file-zipper',
                        in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => 'fa-file-image',
                        in_array($ext, ['mp4', 'avi', 'mov', 'mkv', 'webm', 'mp3', 'wav', 'ogg', 'flac']) => 'fa-file-audio',
                        default                                           => 'fa-file-lines',
                    };
                    // Rodzaj pliku stoi obok tytułu (np. „Klauzula (DOCX)"), pod spodem zostaje sam rozmiar.
                    $type = strtoupper(trim((string) ($attachment->file_extension ?? '')));
                    $meta = trim((string) ($attachment->file_size ?? ''));
                @endphp
                <li class="dl-card">
                    <div class="dl-top">
                        <i class="fa-solid {{ $fileIcon }} dl-icon" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="dl-name">{{ $attachment->label }}@if ($type !== '') <span class="dl-ext">({{ $type }})</span>@endif</p>
                            @if ($meta !== '')<p class="dl-meta">{{ $meta }}</p>@endif
                        </div>
                    </div>
                    <a href="{{ $attachment->file_url }}" download class="dl-btn">
                        <i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Pobierz<span class="sr-only"> plik: {{ $attachment->label }}@if ($type !== '' || $meta !== '') ({{ collect([$type, $meta])->filter()->implode(', ') }})@endif</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
