@extends('admin.layout')
@section('title', 'Import subskrybentów')
@section('content')
    @include('newsletter::admin.partials.flash')
    <form method="POST" action="{{ route('admin.newsletter.subskrybenci.import.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6" x-data="{ status: 'pending' }">
        @csrf
        <div>
            <label for="file" class="mb-1 block text-sm font-bold">Plik CSV lub JSON (max 10 MB)</label>
            <input id="file" name="file" type="file" accept=".csv,.txt,.json" required class="w-full rounded border border-gray-300 text-sm file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-bold">
            <p class="mt-1 text-xs text-muted">CSV: nagłówek z kolumnami <code>email</code>, opcjonalnie <code>name</code>, <code>phone</code>, <code>topics</code> (np. „news,events”); separator „;” lub „,”. JSON: tablica obiektów z tymi samymi kluczami. Duplikaty po adresie są scalane, adresy zanonimizowane/zablokowane pomijane.</p>
        </div>
        <fieldset>
            <legend class="mb-1 text-sm font-bold">Status po imporcie</legend>
            <label class="flex items-start gap-2 text-sm"><input type="radio" name="status" value="pending" x-model="status" class="mt-1 border-gray-300 text-brand focus:ring-brand"> <span><strong>Oczekujący</strong> — do każdego adresu wyślemy e-mail potwierdzający (double opt-in). Bezpieczny wybór dla list z nieznanym źródłem zgody.</span></label>
            <label class="mt-2 flex items-start gap-2 text-sm"><input type="radio" name="status" value="confirmed" x-model="status" class="mt-1 border-gray-300 text-brand focus:ring-brand"> <span><strong>Aktywny</strong> — bez potwierdzenia. Tylko gdy posiadasz udokumentowaną zgodę (np. z innego systemu). Fakt importu i źródło zgody trafią do rejestru zgód.</span></label>
        </fieldset>
        <div x-show="status === 'confirmed'" x-cloak class="space-y-3 rounded border border-amber-300 bg-amber-50 p-4">
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="has_consent" value="1" class="mt-1 rounded border-gray-300 text-brand focus:ring-brand"> <span>Oświadczam, że dla wszystkich adresów w pliku organizacja posiada ważną zgodę na otrzymywanie newslettera.</span></label>
            <div><label for="consent_source" class="mb-1 block text-sm font-bold">Źródło zgody</label><input id="consent_source" name="consent_source" value="{{ old('consent_source') }}" placeholder="np. baza SZO, eksport 2026-09" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="consent_date" class="mb-1 block text-sm font-bold">Data zgody (opcjonalnie)</label><input id="consent_date" name="consent_date" type="date" value="{{ old('consent_date') }}" class="rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="list_id" class="mb-1 block text-sm font-bold">Dodaj do listy</label><select id="list_id" name="list_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="">— nie dodawaj —</option>@foreach ($lists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
            <div><label for="tag" class="mb-1 block text-sm font-bold">Tag importu</label><input id="tag" name="tag" value="{{ old('tag', 'import-' . now()->format('Y-m-d')) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        </div>
        <fieldset><legend class="mb-1 text-sm font-bold">Domyślne tematy (gdy plik ich nie podaje)</legend><div class="flex flex-wrap gap-3">@foreach ($topics as $k => $l)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="topics[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked($k === 'news')> {{ $l }}</label>@endforeach</div></fieldset>
        <div class="flex gap-2 border-t border-gray-200 pt-4">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Importuj</button>
            <a href="{{ route('admin.newsletter.subskrybenci.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a>
        </div>
    </form>
@endsection
