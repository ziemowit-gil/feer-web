@php $attachments ??= collect(); @endphp

@if ($attachments->isNotEmpty())
    @include('partials.attachments-css')
    @php $dlId = 'dl-h-'.\Illuminate\Support\Str::random(6); @endphp
    <section class="dl-frame" aria-labelledby="{{ $dlId }}">
        <h2 id="{{ $dlId }}" class="dl-h"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Pliki do pobrania</h2>
        <ul role="list" class="dl-grid">
            @foreach ($attachments as $attachment)
                @php
                    $ext = strtolower($attachment->file_extension ?? '');
                    [$kind, $fileIcon] = match (true) {
                        $ext === 'pdf'                                    => ['is-pdf', 'fa-file-pdf'],
                        in_array($ext, ['doc', 'docx', 'odt', 'rtf'])     => ['is-doc', 'fa-file-word'],
                        in_array($ext, ['xls', 'xlsx', 'ods', 'csv'])     => ['is-xls', 'fa-file-excel'],
                        in_array($ext, ['ppt', 'pptx', 'odp'])            => ['is-ppt', 'fa-file-powerpoint'],
                        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz']) => ['is-zip', 'fa-file-zipper'],
                        in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => ['is-img', 'fa-file-image'],
                        in_array($ext, ['mp4', 'avi', 'mov', 'mkv', 'webm', 'mp3', 'wav', 'ogg', 'flac']) => ['is-av', 'fa-file-audio'],
                        default                                           => ['', 'fa-file-arrow-down'],
                    };
                    $meta = trim(strtoupper($attachment->file_extension ?? '').($attachment->file_size ? ', '.$attachment->file_size : ''), ', ');
                @endphp
                <li class="dl-card">
                    <div class="dl-top">
                        <span class="dl-type {{ $kind }}" aria-hidden="true"><i class="fa-solid {{ $fileIcon }}"></i>{{ strtoupper(\Illuminate\Support\Str::limit($ext ?: 'plik', 4, '')) }}</span>
                        <div>
                            <p class="dl-name">{{ $attachment->label }}</p>
                            @if ($meta !== '')<p class="dl-meta">{{ $meta }}</p>@endif
                        </div>
                    </div>
                    <a href="{{ $attachment->file_url }}" download class="dl-btn">
                        <i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Pobierz<span class="sr-only"> plik: {{ $attachment->label }}@if ($meta !== '') ({{ $meta }})@endif</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
