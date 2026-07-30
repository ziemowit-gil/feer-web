@extends('admin.layout')

@section('title', 'Do zatwierdzenia')

@section('content')
    <p class="mb-4 text-sm text-muted">Treści zgłoszone przez edytorów, oczekujące na zatwierdzenie i publikację.</p>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Zgłosił(a)</th>
                    <th class="px-4 py-3">Zaktualizowano</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700">{{ $item['label'] }}</span></td>
                        <td class="px-4 py-3 font-medium text-ink">
                            <a href="{{ $item['edit_url'] }}" class="text-brand hover:text-brand-dark">{{ $item['title'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $item['submitted_by'] }}</td>
                        <td class="px-4 py-3 text-muted">{{ $item['updated_at']?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ $item['edit_url'] }}" class="rounded px-2 py-1 text-xs font-bold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Podgląd / edycja</a>
                                <form method="POST" action="{{ route('admin.zatwierdzanie.approve', ['type' => $item['type'], 'id' => $item['id']]) }}">
                                    @csrf
                                    <button type="submit" class="rounded bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i> Zatwierdź i opublikuj
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.zatwierdzanie.reject', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                    x-data="{ open: false }"
                                    @submit="if (! open) { $event.preventDefault(); open = true; $nextTick(() => $refs.reason.focus()); }"
                                    class="flex items-center gap-2">
                                    @csrf
                                    <label x-show="open" x-cloak class="sr-only" for="reason-{{ $item['type'] }}-{{ $item['id'] }}">Powód odrzucenia (opcjonalnie)</label>
                                    <input x-show="open" x-cloak x-ref="reason" id="reason-{{ $item['type'] }}-{{ $item['id'] }}"
                                        type="text" name="reason" maxlength="1000" placeholder="Powód (opcjonalnie)"
                                        class="w-44 rounded border-gray-300 text-xs focus:border-red-500 focus:ring-red-500">
                                    <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-red-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                                        <span x-text="open ? 'Potwierdź odrzucenie' : 'Odrzuć'">Odrzuć</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-muted">
                            <i class="fa-solid fa-circle-check mb-2 block text-2xl text-green-600" aria-hidden="true"></i>
                            Brak treści oczekujących na zatwierdzenie.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
