@php
    $attachments ??= collect();
    $brandSections ??= [];
@endphp

<div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 sm:p-8">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-ink">Pliki do pobrania</h2>
            <p class="text-sm text-muted">Dokumenty, które odwiedzający mogą pobrać ze strony.</p>
        </div>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-ink">{{ $attachments->count() }}</span>
    </div>

    @if ($attachments->isNotEmpty())
        @php
            $fileIcon = fn ($ext) => match (strtolower(ltrim((string) $ext, '.'))) {
                'pdf' => 'fa-file-pdf', 'doc', 'docx', 'odt' => 'fa-file-word', 'xls', 'xlsx', 'ods', 'csv' => 'fa-file-excel',
                'ppt', 'pptx', 'odp' => 'fa-file-powerpoint', 'zip', 'rar', '7z' => 'fa-file-zipper', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'fa-file-image',
                'mp3', 'wav', 'ogg' => 'fa-file-audio', 'mp4', 'mov', 'webm' => 'fa-file-video', default => 'fa-file',
            };
        @endphp
        <ul class="grid gap-3 sm:grid-cols-2" role="list">
            @foreach ($attachments as $attachment)
                <li class="flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-lg bg-white text-xl text-brand-dark" aria-hidden="true"><i class="fa-solid {{ $fileIcon($attachment->file_extension) }}"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink">{{ $attachment->label }}</p>
                        <p class="text-xs text-muted">
                            {{ strtoupper(ltrim((string) $attachment->file_extension, '.')) }} &middot; {{ $attachment->file_size }}
                            @if ($attachment->group)
                                &middot; <span class="font-mono">{{ $attachment->group }}</span>
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.pliki.destroy', $attachment) }}" onsubmit="return confirm('Usunąć plik &quot;{{ $attachment->label }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex h-10 w-10 flex-none items-center justify-center rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600" title="Usuń" aria-label="Usuń plik {{ $attachment->label }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <div class="rounded-lg bg-gray-50 p-6 text-center text-sm text-muted">
            <i class="fa-solid fa-folder-open mb-2 block text-2xl text-gray-400" aria-hidden="true"></i>
            Brak plików. Dodaj pierwszy poniżej.
        </div>
    @endif

    {{-- Dodawanie: strefa upuszczania pliku (kliknięcie lub przeciągnięcie); nazwa uzupełnia się z nazwy pliku, gdy jest pusta. --}}
    <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="space-y-5 border-t border-gray-100 pt-6"
        x-data="{ name: '', over: false, pick(f) { this.name = f ? f.name : ''; const l = document.getElementById('attachment_label'); if (f && l && l.value.trim() === '') { l.value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' '); } } }">
        @csrf
        <h3 class="text-sm font-bold text-ink">Dodaj plik</h3>

        <label for="attachment_file"
            @dragover.prevent="over = true" @dragleave.prevent="over = false"
            @drop.prevent="over = false; $refs.file.files = $event.dataTransfer.files; pick($refs.file.files[0])"
            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-8 text-center transition focus-within:ring-2 focus-within:ring-brand focus-within:ring-offset-2"
            :class="over ? 'border-brand bg-brand-light' : 'border-gray-300 bg-gray-50 hover:border-gray-400'">
            <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-dark" aria-hidden="true"></i>
            <span class="text-sm font-bold text-ink" x-text="name || 'Kliknij, aby wybrać plik, albo przeciągnij go tutaj'">Kliknij, aby wybrać plik, albo przeciągnij go tutaj</span>
            <span class="text-xs text-muted">Dowolny dokument lub obraz</span>
            <input x-ref="file" type="file" id="attachment_file" name="file" required class="sr-only" @change="pick($event.target.files[0])">
        </label>
        @error('file') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

        <div class="grid gap-5 {{ !empty($brandSections) ? 'sm:grid-cols-2' : '' }}">
            <div>
                <label for="attachment_label" class="mb-1.5 block text-sm font-bold text-ink">Nazwa pliku</label>
                <input type="text" id="attachment_label" name="label" value="{{ old('label') }}" placeholder="np. Logotyp kolorowy JPEG" required
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                @error('label') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            @if (!empty($brandSections))
                <div>
                    <label for="attachment_group" class="mb-1.5 block text-sm font-bold text-ink">Sekcja</label>
                    <select id="attachment_group" name="group" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <option value="">— bez sekcji —</option>
                        @foreach ($brandSections as $bs)
                            <option value="{{ $bs['key'] }}">{{ $bs['title'] }}</option>
                        @endforeach
                    </select>
                    @error('group') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>Dodaj plik
        </button>
    </form>
</div>
