@extends('admin.layout')

@section('title', 'Rejestr pełnomocnictw')

@section('content')
    {{-- Pasek narzędzi: wyszukiwarka + filtr rodzaju + dodawanie --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" action="{{ route('admin.pelnomocnictwa.index') }}" x-data
              class="flex flex-1 flex-wrap items-end gap-3" role="search" aria-label="Wyszukiwarka rejestru">
            <div class="min-w-[14rem] flex-1">
                <label for="f-q" class="mb-1 block text-xs font-semibold text-muted">Szukaj</label>
                <input id="f-q" type="search" name="q" value="{{ $q }}" placeholder="Nazwisko, numer lub zakres…"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="f-typ" class="mb-1 block text-xs font-semibold text-muted">Rodzaj</label>
                <select id="f-typ" name="typ" @change="$el.form.requestSubmit()"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <option value="">Wszystkie</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-magnifying-glass mr-1" aria-hidden="true"></i> Szukaj
            </button>
            @if ($q !== '' || $type !== '')
                <a href="{{ route('admin.pelnomocnictwa.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wyczyść</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('authorizations.index') }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Strona publiczna
            </a>
            <a href="{{ route('admin.pelnomocnictwa.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <caption class="sr-only">Rejestr pełnomocnictw i upoważnień — widok administracyjny</caption>
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Nr dokumentu</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Rodzaj</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Umocowany</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Zakres</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Ważność</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-muted">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($authorizations as $item)
                    @php $status = $item->status(); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-ink">{{ $item->document_number }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->typeLabel() }}</td>
                        <td class="px-4 py-3 font-semibold text-ink">
                            {{ $item->grantee_name }}
                            <span class="block text-xs font-normal text-muted">udzielił: {{ $item->principal }}</span>
                        </td>
                        <td class="max-w-xs truncate px-4 py-3 text-muted" title="{{ $item->scope }}">{{ $item->scope }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">
                            {{ $item->valid_from->format('d.m.Y') }} – {{ $item->valid_to?->format('d.m.Y') ?? 'bezterminowo' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                {{ ['active' => 'bg-green-100 text-green-800', 'upcoming' => 'bg-sky-100 text-sky-800', 'expired' => 'bg-gray-200 text-gray-700', 'revoked' => 'bg-red-100 text-red-800'][$status] }}">
                                {{ $status === 'upcoming' ? 'Od ' . $item->valid_from->format('d.m.Y') : \App\Models\Authorization::STATUSES[$status] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.pelnomocnictwa.aktywnosc', $item) }}" class="inline"
                                  @if ($item->is_active) data-confirm="Unieważnić dokument „{{ $item->document_number }}"? Wpis pozostanie w rejestrze ze statusem „Unieważnione"." @endif>
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="rounded-lg p-2 text-muted hover:bg-amber-50 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        aria-label="{{ $item->is_active ? 'Unieważnij' : 'Przywróć' }} dokument {{ $item->document_number }}"
                                        title="{{ $item->is_active ? 'Unieważnij' : 'Przywróć' }}">
                                    <i class="fa-solid {{ $item->is_active ? 'fa-ban' : 'fa-rotate-left' }}" aria-hidden="true"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.pelnomocnictwa.edit', $item) }}"
                               class="inline-block rounded-lg p-2 text-muted hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                               aria-label="Edytuj wpis {{ $item->document_number }}">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.pelnomocnictwa.destroy', $item) }}" class="inline"
                                  data-confirm="Usunąć wpis „{{ $item->document_number }}" z rejestru? Tej operacji nie można cofnąć — do unieważnienia służy osobny przycisk.">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg p-2 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                        aria-label="Usuń wpis {{ $item->document_number }}">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.partials.empty-state', [
                        'colspan' => 7,
                        'icon' => 'fa-file-signature',
                        'message' => ($q !== '' || $type !== '') ? 'Brak wpisów dla podanych kryteriów.' : 'Rejestr jest pusty — dodaj pierwszy wpis.',
                        'createRoute' => route('admin.pelnomocnictwa.create'),
                        'createLabel' => 'Dodaj wpis',
                    ])
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $authorizations->links() }}
    </div>
@endsection
