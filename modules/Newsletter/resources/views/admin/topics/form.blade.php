@extends('admin.layout')
@section('title', $topic->exists ? 'Temat: ' . $topic->label : 'Nowy temat')
@section('content')
    @include('newsletter::admin.partials.flash')
    @php $inp = 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand'; @endphp
    <form method="POST" action="{{ $topic->exists ? route('admin.newsletter.tematy.update', $topic) : route('admin.newsletter.tematy.store') }}" class="max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf @if ($topic->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="label" class="mb-1 block text-sm font-bold">Nazwa wyświetlana</label><input id="label" name="label" required maxlength="80" value="{{ old('label', $topic->label) }}" class="{{ $inp }}"></div>
            <div><label for="key" class="mb-1 block text-sm font-bold">Klucz techniczny</label><input id="key" name="key" maxlength="40" value="{{ old('key', $topic->key) }}" class="{{ $inp }} font-mono text-sm" @disabled($topic->exists) placeholder="z nazwy"><p class="mt-1 text-xs text-muted">{{ $topic->exists ? 'Stały — zapisany w danych subskrybentów.' : 'Małe litery, cyfry, _ i -. Puste = z nazwy.' }}</p></div>
        </div>
        <div><label for="description" class="mb-1 block text-sm font-bold">Opis (podpowiedź w formularzu)</label><input id="description" name="description" maxlength="255" value="{{ old('description', $topic->description) }}" class="{{ $inp }}"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="icon" class="mb-1 block text-sm font-bold">Ikona (Font Awesome)</label><input id="icon" name="icon" value="{{ old('icon', $topic->icon) }}" placeholder="fa-newspaper" class="{{ $inp }}"></div>
            <div><label for="color" class="mb-1 block text-sm font-bold">Kolor (opcjonalnie)</label><input id="color" name="color" value="{{ old('color', $topic->color) }}" placeholder="#1752BF" class="{{ $inp }}" data-admin-color-input></div>
        </div>
        <fieldset><legend class="mb-1 text-sm font-bold">Źródła treści dynamicznej dla tego tematu</legend><p class="mb-2 text-xs text-muted">Używane przez blok „Najnowsze aktualności” z włączonym dopasowaniem do tematów subskrybenta.</p>
            <div class="flex flex-wrap gap-3 text-sm">@foreach ($sources as $k => $l)<label class="inline-flex items-center gap-2"><input type="checkbox" name="feed_sources[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($k, old('feed_sources', $topic->feed_sources ?? []), true))> {{ $l }}</label>@endforeach</div></fieldset>
        @if ($categories->isNotEmpty())
        <fieldset><legend class="mb-1 text-sm font-bold">Kategorie aktualności przypisane do tematu</legend><p class="mb-2 text-xs text-muted">Aktualność z tych kategorii trafi do subskrybentów tego tematu (bez przypisania: temat „Aktualności”).</p>
            <div class="flex flex-wrap gap-3 text-sm">@foreach ($categories as $c)<label class="inline-flex items-center gap-2"><input type="checkbox" name="news_category_slugs[]" value="{{ $c->slug }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($c->slug, old('news_category_slugs', $topic->news_category_slugs ?? []), true))> <span class="inline-block h-3 w-3 rounded-full" style="background: {{ $c->color ?: '#1752BF' }}" aria-hidden="true"></span> {{ $c->name }}</label>@endforeach</div></fieldset>
        @endif
        <div class="flex flex-wrap gap-4 text-sm">
            <label class="inline-flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('is_active', $topic->is_active ?? true))> Aktywny (widoczny w formularzach i preferencjach)</label>
            <label class="inline-flex items-center gap-2"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('is_default', $topic->is_default))> Domyślnie zaznaczony w nowych formularzach</label>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.newsletter.tematy.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a>
        </div>
    </form>
    @if ($topic->exists)
    <form method="POST" action="{{ route('admin.newsletter.tematy.destroy', $topic) }}" class="mt-4 max-w-3xl rounded-lg border border-red-200 bg-white p-6" onsubmit="return confirm('Usunąć temat? Subskrybenci zostaną zaktualizowani zgodnie z wyborem poniżej.')">
        @csrf @method('DELETE')
        <h2 class="mb-2 text-sm font-bold uppercase text-red-800">Usuń temat</h2>
        <div class="flex flex-wrap items-end gap-3">
            <div><label for="merge_into" class="mb-1 block text-sm font-bold">Subskrybentów tego tematu</label><select id="merge_into" name="merge_into" class="rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="">tylko odepnij od tematu</option>@foreach ($others as $o)<option value="{{ $o->key }}">przepisz na „{{ $o->label }}”</option>@endforeach</select></div>
            <button class="rounded border border-red-300 bg-white px-5 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Usuń temat</button>
        </div>
    </form>
    @endif
@endsection
