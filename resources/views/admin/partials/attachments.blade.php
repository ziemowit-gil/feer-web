@php
    $attachments ??= collect();
    $brandSections ??= [];
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-6">
    <p class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Pliki do pobrania</p>

    @if ($attachments->isNotEmpty())
        <ul class="mb-4 divide-y divide-gray-100 rounded border border-gray-200">
            @foreach ($attachments as $attachment)
                <li class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">{{ $attachment->label }}</p>
                        <p class="text-xs text-muted">
                            {{ $attachment->file_extension }} &middot; {{ $attachment->file_size }}
                            @if ($attachment->group)
                                &middot; <span class="font-mono">{{ $attachment->group }}</span>
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.pliki.destroy', $attachment) }}" onsubmit="return confirm('Usunąć plik &quot;{{ $attachment->label }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex-none text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mb-4 text-sm text-muted">Brak plików. Dodaj pierwszy poniżej.</p>
    @endif

    <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="space-y-3 border-t border-gray-100 pt-4">
        @csrf
        <div>
            <label for="attachment_label" class="mb-1 block text-sm font-bold">Nazwa pliku</label>
            <input type="text" id="attachment_label" name="label" value="{{ old('label') }}" placeholder="np. Logotyp kolorowy JPEG" required
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (!empty($brandSections))
            <div>
                <label for="attachment_group" class="mb-1 block text-sm font-bold">Sekcja</label>
                <select id="attachment_group" name="group" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <option value="">— bez sekcji —</option>
                    @foreach ($brandSections as $bs)
                        <option value="{{ $bs['key'] }}">{{ $bs['title'] }}</option>
                    @endforeach
                </select>
                @error('group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="attachment_file" class="mb-1 block text-sm font-bold">Plik</label>
            <input type="file" id="attachment_file" name="file" required
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj plik
        </button>
    </form>
</div>
