@php $images = $page->images; @endphp

<div class="p-6 rounded-md bg-white">
    <p class="mb-1 text-sm font-bold uppercase tracking-wide text-muted">Galeria zdjęć</p>
    <p class="mb-4 text-xs text-muted">Zdjęcia pojawią się w galerii strony, a po włączeniu „Slider ze zdjęć strony” (Dodatki) — w sliderze nad treścią. Tytuł, tekst i przycisk są opcjonalne i widać je tylko w sliderze. Kolejność ustalasz polem „Kolejność".</p>

    @if ($images->isNotEmpty())
        <ul class="mb-4 grid gap-3 sm:grid-cols-2">
            @foreach ($images as $image)
                <li class="flex gap-3 p-3 rounded-md bg-gray-50">
                    @if ($image->image_url)
                        <img src="{{ $image->image_url }}" alt="{{ $image->alt }}" class="h-20 w-24 flex-none rounded object-cover">
                    @endif
                    <form method="POST" action="{{ route('admin.podstrony.zdjecia.update', $image) }}" class="min-w-0 grow space-y-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="alt" value="{{ $image->alt }}" placeholder="Opis alternatywny (WCAG)" aria-label="Opis alternatywny zdjęcia"
                            class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                        <input type="text" name="caption" value="{{ $image->caption }}" placeholder="Podpis (opcjonalnie)" aria-label="Podpis zdjęcia"
                            class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                        <details @if ($image->hasSlideContent() || $errors->has('button_url')) open @endif class="rounded bg-white px-2 py-1">
                            <summary class="cursor-pointer text-xs font-bold text-ink">Treść slajdu <span class="font-normal text-muted">(slider)</span></summary>
                            <div class="mt-2 space-y-2 pb-1">
                                <input type="text" name="title" value="{{ $image->title }}" maxlength="255" placeholder="Tytuł slajdu" aria-label="Tytuł slajdu"
                                    class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                <textarea name="text" rows="2" maxlength="500" placeholder="Tekst slajdu" aria-label="Tekst slajdu"
                                    class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">{{ $image->text }}</textarea>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <input type="text" name="button_label" value="{{ $image->button_label }}" maxlength="50" placeholder="Napis na przycisku" aria-label="Napis na przycisku slajdu"
                                        class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                    <input type="text" name="button_url" value="{{ $image->button_url }}" maxlength="255" placeholder="Adres przycisku, np. /kontakt" aria-label="Adres przycisku slajdu"
                                        class="w-full rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                </div>
                            </div>
                        </details>
                        <div class="flex items-center gap-2">
                            <input type="number" name="order" value="{{ $image->order }}" min="0" aria-label="Kolejność zdjęcia"
                                class="w-20 rounded border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                            <button type="submit" class="rounded border border-brand-dark px-2 py-1 text-xs font-bold text-brand-dark hover:bg-brand-light">Zapisz</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.podstrony.zdjecia.destroy', $image) }}" onsubmit="return confirm('Usunąć to zdjęcie z galerii?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex-none text-muted hover:text-red-600" title="Usuń zdjęcie" aria-label="Usuń zdjęcie"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
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
                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="page_image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny <span class="font-normal text-muted">(WCAG)</span></label>
                <input type="text" id="page_image_alt" name="alt" value="{{ old('alt') }}" placeholder="np. Zespół fundacji podczas warsztatów"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                @error('alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="page_image_caption" class="mb-1 block text-sm font-bold">Podpis <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="text" id="page_image_caption" name="caption" value="{{ old('caption') }}"
                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <fieldset class="rounded-md bg-gray-50 p-3">
            <legend class="px-1 text-sm font-bold">Treść slajdu <span class="font-normal text-muted">(opcjonalnie, widoczna w sliderze)</span></legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="page_image_title" class="mb-1 block text-sm font-bold">Tytuł</label>
                    <input type="text" id="page_image_title" name="title" value="{{ old('title') }}" maxlength="255"
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                </div>
                <div class="sm:col-span-2">
                    <label for="page_image_text" class="mb-1 block text-sm font-bold">Tekst</label>
                    <textarea id="page_image_text" name="text" rows="2" maxlength="500"
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">{{ old('text') }}</textarea>
                </div>
                <div>
                    <label for="page_image_button_label" class="mb-1 block text-sm font-bold">Napis na przycisku</label>
                    <input type="text" id="page_image_button_label" name="button_label" value="{{ old('button_label') }}" maxlength="50"
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                </div>
                <div>
                    <label for="page_image_button_url" class="mb-1 block text-sm font-bold">Adres przycisku</label>
                    <input type="text" id="page_image_button_url" name="button_url" value="{{ old('button_url') }}" maxlength="255" placeholder="np. /kontakt lub https://…"
                        @error('button_url') aria-invalid="true" aria-describedby="page_image_button_url_error" @enderror
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    @error('button_url') <p id="page_image_button_url_error" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>
        <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj zdjęcie
        </button>
    </form>
</div>
