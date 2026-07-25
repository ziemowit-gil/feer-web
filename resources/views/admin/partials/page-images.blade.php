@php $images = $page->images; @endphp

<div class="rounded-lg border border-gray-200 bg-white p-6">
    <p class="mb-1 text-sm font-bold uppercase tracking-wide text-muted">Galeria zdjęć</p>
    <p class="mb-4 text-xs text-muted">Zdjęcia pojawią się na stronie typu „O organizacji”. Kolejność ustalasz polem „Kolejność”.</p>

    @if ($images->isNotEmpty())
        <ul class="mb-4 grid gap-3 sm:grid-cols-2">
            @foreach ($images as $image)
                <li class="flex gap-3 rounded border border-gray-200 p-3">
                    @if ($image->image_url)
                        <img src="{{ $image->image_url }}" alt="{{ $image->alt }}" class="h-20 w-24 flex-none rounded object-cover">
                    @endif
                    <form method="POST" action="{{ route('admin.podstrony.zdjecia.update', $image) }}" class="min-w-0 grow space-y-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="alt" value="{{ $image->alt }}" placeholder="Opis alternatywny (WCAG)" aria-label="Opis alternatywny zdjęcia"
                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <input type="text" name="caption" value="{{ $image->caption }}" placeholder="Podpis (opcjonalnie)" aria-label="Podpis zdjęcia"
                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <div class="flex items-center gap-2">
                            <input type="number" name="order" value="{{ $image->order }}" min="0" aria-label="Kolejność zdjęcia"
                                class="w-20 rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            <button type="submit" class="rounded border border-brand px-2 py-1 text-xs font-bold text-brand hover:bg-brand-light">Zapisz</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.podstrony.zdjecia.destroy', $image) }}" onsubmit="return confirm('Usunąć to zdjęcie z galerii?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex-none text-muted hover:text-red-600" title="Usuń zdjęcie" aria-label="Usuń zdjęcie"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mb-4 text-sm text-muted">Brak zdjęć. Dodaj pierwsze poniżej.</p>
    @endif

    <form method="POST" action="{{ route('admin.podstrony.zdjecia.store', $page) }}" enctype="multipart/form-data" class="space-y-3 border-t border-gray-100 pt-4">
        @csrf
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="page_image_file" class="mb-1 block text-sm font-bold">Zdjęcie</label>
                <input type="file" id="page_image_file" name="image" accept="image/*" required
                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="page_image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny <span class="font-normal text-muted">(WCAG)</span></label>
                <input type="text" id="page_image_alt" name="alt" value="{{ old('alt') }}" placeholder="np. Zespół fundacji podczas warsztatów"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                @error('alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="page_image_caption" class="mb-1 block text-sm font-bold">Podpis <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="text" id="page_image_caption" name="caption" value="{{ old('caption') }}"
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj zdjęcie
        </button>
    </form>
</div>
