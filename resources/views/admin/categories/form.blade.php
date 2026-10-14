@extends('admin.layout')

@section('title', $category->exists ? 'Edytuj kategorię' : 'Nowa kategoria')

@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.kategorie.update', $category) : route('admin.kategorie.store') }}" class="max-w-xl space-y-5 p-6 rounded-md bg-white">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted">/kategoria/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="zostanie wygenerowany z nazwy"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            </div>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'icon'))
            <div>
                <div class="mb-1 flex items-center"><label for="icon" class="text-sm font-bold">Ikona <span class="font-normal text-muted">(opcjonalnie)</span></label>@include('admin.partials.help', ['topic' => 'ikona kategorii', 'text' => 'Ikona stoi przy nazwie kategorii na liście działań. Puste pole = ikona dobrana automatycznie do nazwy.'])</div>
                <input type="text" id="icon" name="icon" value="{{ old('icon', $category->icon) }}" data-icon-picker data-icon-format="name" placeholder="{{ $category->exists ? $category->iconValue() : 'np. bi-easel' }}"
                    class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark">
                @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $category->order) }}"
                class="w-28 rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>

        @if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_slug') && \Illuminate\Support\Facades\Route::has('admin.portale.index'))
            <div class="space-y-2 border-t border-gray-100 pt-5">
                <p class="text-base font-bold text-ink">Portal kategorii</p>
                @if ($category->exists)
                    <p class="text-sm text-ink">{{ $category->portal_slug ? 'Ta kategoria ma portal /portal/'.$category->portal_slug.'.' : 'Ta kategoria nie ma jeszcze portalu.' }}
                        <a href="{{ route('admin.portale.edit', $category) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink">{{ $category->portal_slug ? 'Ustawienia portalu' : 'Utwórz portal' }}</a></p>
                @else
                    <p class="text-sm text-muted">Portal utworzysz po zapisaniu kategorii — w menu Portale.</p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.kategorie.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
