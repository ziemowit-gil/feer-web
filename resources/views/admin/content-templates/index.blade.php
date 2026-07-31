@extends('admin.layout')

@section('title', 'Szablony treści')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">Zapisane szablony do szybkiego wypełniania nowych treści.</p>
    </div>

    @if ($templates->isEmpty())
        <p class="rounded-lg border border-gray-200 bg-white px-6 py-8 text-center text-muted">
            Brak szablonów. Użyj przycisku <strong>Zapisz jako szablon</strong> w formularzu newsa lub wydarzenia.
        </p>
    @else
        @foreach ($templates->groupBy('type') as $type => $group)
            <h2 class="mb-2 mt-6 text-sm font-bold uppercase tracking-wide text-muted">
                {{ match ($type) { 'news' => 'Aktualności', 'event' => 'Wydarzenia', 'volunteer_ad' => 'Wolontariat', default => $type } }}
            </h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                        <tr>
                            <th class="px-4 py-3">Nazwa szablonu</th>
                            <th class="px-4 py-3">Utworzony</th>
                            <th class="px-4 py-3 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($group as $template)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $template->name }}</td>
                                <td class="px-4 py-3 text-muted">{{ $template->created_at->format('d.m.Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <form method="POST" action="{{ route('admin.szablony.destroy', $template) }}"
                                            onsubmit="return confirm('Usunąć szablon „{{ $template->name }}"?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-muted hover:text-red-600" title="Usuń szablon">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
@endsection
