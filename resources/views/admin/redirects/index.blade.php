@extends('admin.layout')

@section('title', 'Przekierowania 301')

@section('content')
    <div class="max-w-4xl space-y-6">
        <p class="text-sm text-muted">Stałe przekierowania (301) — przydatne po zmianie adresów lub przeprowadzce. Ścieżka źródłowa to adres na tej stronie (np. <code>/stary-adres</code>), cel to ścieżka <code>/nowy</code> lub pełny URL. Przekierowania wchodzą też do paczki eksportu treści.</p>

        {{-- Dodawanie --}}
        <form method="POST" action="{{ route('admin.przekierowania.store') }}" class="rounded-lg border border-gray-200 bg-white p-5">
            @csrf
            <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div>
                    <label for="from_path" class="mb-1 block text-sm font-bold">Ścieżka źródłowa</label>
                    <input type="text" id="from_path" name="from_path" value="{{ old('from_path') }}" placeholder="/stary-adres" required
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    @error('from_path') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="to_url" class="mb-1 block text-sm font-bold">Cel</label>
                    <input type="text" id="to_url" name="to_url" value="{{ old('to_url') }}" placeholder="/nowy-adres lub https://…" required
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    @error('to_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Dodaj</button>
            </div>
        </form>

        {{-- CSV import/eksport --}}
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <a href="{{ route('admin.przekierowania.export') }}" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-ink hover:border-brand hover:text-brand">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksport CSV
            </a>
            <form method="POST" action="{{ route('admin.przekierowania.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" required
                    class="text-sm text-muted file:mr-2 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-brand-dark">
                <button type="submit" class="rounded border border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">Importuj CSV</button>
            </form>
            <span class="text-xs text-muted">Kolumny CSV: <code>from_path, to_url, is_active</code>.</span>
        </div>

        {{-- Lista --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Ze ścieżki</th>
                        <th class="px-4 py-3">Do</th>
                        <th class="px-4 py-3">Aktywne</th>
                        <th class="px-4 py-3">Trafienia</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($redirects as $r)
                        <tr>
                            <form method="POST" action="{{ route('admin.przekierowania.update', $r) }}" id="redirect-{{ $r->id }}">@csrf @method('PUT')</form>
                            <td class="px-4 py-3"><input form="redirect-{{ $r->id }}" type="text" name="from_path" value="{{ $r->from_path }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand"></td>
                            <td class="px-4 py-3"><input form="redirect-{{ $r->id }}" type="text" name="to_url" value="{{ $r->to_url }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand"></td>
                            <td class="px-4 py-3"><input form="redirect-{{ $r->id }}" type="checkbox" name="is_active" value="1" {{ $r->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-brand focus:ring-brand"></td>
                            <td class="px-4 py-3 text-muted">{{ $r->hits }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <button form="redirect-{{ $r->id }}" type="submit" class="text-brand hover:text-brand-dark" title="Zapisz"><i class="fa-solid fa-floppy-disk"></i></button>
                                    <form method="POST" action="{{ route('admin.przekierowania.destroy', $r) }}" onsubmit="return confirm('Usunąć przekierowanie {{ $r->from_path }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-muted">Brak przekierowań.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
