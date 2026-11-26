@extends('admin.layout')

@section('title', 'FAQ — najczęstsze pytania')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Najczęstsze pytania (strona <a href="{{ route('faq.index') }}" target="_blank" rel="noopener" class="text-brand-dark underline">/faq</a>).</p>
        <a href="{{ route('admin.faq.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie
        </a>
    </div>

    {{-- Sekcja FAQ na dowolnej podstronie: wstawka [faq] (App\Support\FaqSection, widok partials/faq-section). --}}
    @php $faqCats = \App\Models\Faq::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'); @endphp
    <section aria-labelledby="faq-embed-title" class="mb-4 rounded-md bg-brand-light p-5 text-sm text-ink">
        <h2 id="faq-embed-title" class="text-base font-bold">Sekcja FAQ na dowolnej podstronie</h2>
        <p class="mt-1">Wpisz wstawkę w treści strony (w edytorze blokowym w bloku tekstu albo wstawki). Pokaże opublikowane pytania w nowym układzie: nagłówek po lewej, rozwijane pytania po prawej.</p>
        <ul class="mt-3 space-y-1.5" role="list">
            <li><code class="rounded bg-white px-1.5 py-0.5 font-mono">[faq]</code> — wszystkie pytania</li>
            <li><code class="rounded bg-white px-1.5 py-0.5 font-mono">[faq: Kategoria]</code> — pytania z jednej kategorii (kilka: po przecinku)</li>
            <li><code class="rounded bg-white px-1.5 py-0.5 font-mono">[faq: Kategoria; tytul=Pytania o szkolenia; wstep=Krótki opis; limit=5; poziom=3]</code> — własny nagłówek, opis, liczba pytań i poziom nagłówka (2–4)</li>
        </ul>
        @if ($faqCats->isNotEmpty())
            <p class="mt-3"><span class="font-bold">Twoje kategorie:</span>
                @foreach ($faqCats as $cat)<code class="mr-1 rounded bg-white px-1.5 py-0.5 font-mono">[faq: {{ $cat }}]</code>@endforeach
            </p>
        @endif
    </section>

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
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
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Opublikowane</span>
                            @else
                                <span class="text-xs font-bold text-muted">Szkic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.faq.edit', $faq) }}" class="text-brand-dark hover:text-ink" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.faq.destroy', $faq) }}" onsubmit="return confirm('Usunąć to pytanie?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                        @include('admin.partials.empty-state', ['icon' => 'fa-circle-question', 'title' => 'Nie ma jeszcze pytań', 'text' => 'Zbierz pytania, które ludzie zadają najczęściej — oszczędzisz czas sobie i im.', 'route' => 'admin.faq.create', 'action' => 'Dodaj pytanie', 'colspan' => 4])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
