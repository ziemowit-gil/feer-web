@php $attachments ??= collect(); @endphp

@if ($attachments->isNotEmpty())
    <div class="mt-10">
        <h2 class="mb-4 text-xl font-bold text-ink">Pliki do pobrania:</h2>

        <div class="divide-y divide-gray-200 rounded-lg border border-gray-200">
            @foreach ($attachments as $attachment)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 flex-none items-center justify-center rounded border border-brand text-brand" aria-hidden="true">
                            <i class="fa-solid fa-download"></i>
                        </span>
                        <span class="truncate font-bold text-ink">{{ $attachment->label }}</span>
                    </div>

                    <div class="ml-auto flex items-center gap-4">
                        <span class="text-sm text-muted">Format: {{ $attachment->file_extension }}, {{ $attachment->file_size }}</span>
                        <a href="{{ $attachment->file_url }}" download
                            class="flex-none rounded bg-brand px-5 py-2 text-sm font-bold uppercase text-white transition hover:bg-brand-dark">
                            Pobierz <span class="sr-only">— {{ $attachment->label }}</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
