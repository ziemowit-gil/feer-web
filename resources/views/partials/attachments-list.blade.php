@php $attachments ??= collect(); @endphp

@if ($attachments->isNotEmpty())
    @if (! request()->attributes->get('dl_css'))
        @php request()->attributes->set('dl_css', true); @endphp
        <style>
            /* Pliki do pobrania: ramka z kartami plików (zwykły CSS, niezależny od zbudowanych klas Tailwinda). */
            .dl-frame { margin-top: 2.5rem; padding: 1.25rem 1.5rem; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; }
            .dl-h { display: flex; align-items: center; gap: .6rem; margin: 0 0 1rem; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
            .dl-h i { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 2px solid var(--color-brand); border-radius: 9999px; color: var(--color-brand); font-size: .85rem; }
            .dl-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); }
            .dl-card { display: flex; flex-direction: column; gap: .75rem; padding: 1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; }
            .dl-top { display: flex; align-items: flex-start; gap: .75rem; }
            .dl-type { display: flex; flex: none; flex-direction: column; align-items: center; justify-content: center; width: 3.25rem; height: 3.75rem; border-radius: .375rem; background: #1d1d1a; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; line-height: 1.1; }
            .dl-type i { margin-bottom: .2rem; font-size: 1.25rem; }
            .dl-type.is-pdf { background: #b91c1c; } .dl-type.is-doc { background: #1d4ed8; } .dl-type.is-xls { background: #166534; } .dl-type.is-ppt { background: #c2410c; } .dl-type.is-zip { background: #4b5563; } .dl-type.is-img { background: #7e22ce; } .dl-type.is-av { background: #0f766e; }
            .dl-name { margin: 0; font-size: 1rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; overflow-wrap: anywhere; }
            .dl-meta { margin: .15rem 0 0; font-size: .85rem; color: #374151; }
            .dl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; margin-top: auto; min-height: 2.75rem; padding: .5rem 1rem; border: 2px solid var(--color-brand); border-radius: .375rem; background: var(--color-brand); color: #fff; font-size: .9rem; font-weight: 800; text-decoration: none; }
            .dl-btn:hover { background: #1d1d1a; border-color: #1d1d1a; }
            .dl-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
        </style>
    @endif
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
