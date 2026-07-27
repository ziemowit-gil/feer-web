@extends('admin.layout')

@section('title', 'FAQ — najczęstsze pytania')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Najczęstsze pytania (strona <a href="{{ route('faq.index') }}" target="_blank" rel="noopener" class="text-brand underline">/faq</a>).</p>
        <a href="{{ route('admin.faq.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj pytanie
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Pytanie</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($faqs as $faq)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $faq->question }}</td>
                        <td class="px-4 py-3 text-muted">{{ $faq->category ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($faq->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowane</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.faq.edit', $faq) }}" class="text-brand hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.faq.destroy', $faq) }}" onsubmit="return confirm('Usunąć to pytanie?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-muted">Brak pytań. <a href="{{ route('admin.faq.create') }}" class="text-brand underline">Dodaj pierwsze</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
