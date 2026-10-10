@extends('admin.layout')
@section('title', 'Tematy subskrypcji')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Tematy, które subskrybent wybiera przy zapisie i w preferencjach. Kolejność tutaj = kolejność w formularzach (przeciągnij wiersze). Klucz tematu jest stały, bo siedzi w danych subskrybentów.</p>
        <a href="{{ route('admin.newsletter.tematy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy temat</a>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-3 py-3 w-8"><span class="sr-only">Kolejność</span></th><th class="px-3 py-3">Temat</th><th class="px-3 py-3">Klucz</th><th class="px-3 py-3">Treść dynamiczna</th><th class="px-3 py-3 text-right">Subskrybenci</th><th class="px-3 py-3 text-right">Aktywni</th><th class="px-3 py-3">Stan</th><th class="px-3 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody id="topics-body" data-reorder-url="{{ route('admin.newsletter.tematy.reorder') }}">
            @forelse ($topics as $t)
                <tr class="border-t border-gray-100 hover:bg-gray-50" draggable="true" data-id="{{ $t->id }}">
                    <td class="px-3 py-2 text-muted"><button type="button" class="cursor-grab" aria-label="Przeciągnij, aby zmienić kolejność: {{ $t->label }}" data-handle><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></button>
                        <span class="sr-only">Kolejność {{ $loop->iteration }}</span></td>
                    <td class="min-w-[16rem] px-3 py-2"><span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs" style="background: {{ $t->color ?: '#EAF1FF' }}; color: {{ $t->color ? '#fff' : '#1752BF' }}" aria-hidden="true"><i class="fa-solid {{ $t->icon ?: 'fa-tag' }}"></i></span>
                        <a href="{{ route('admin.newsletter.tematy.edit', $t) }}" class="ml-2 font-bold text-brand-dark hover:underline">{{ $t->label }}</a>@if($t->is_default) <span class="ml-1 rounded-full bg-brand/10 px-2 py-0.5 text-xs text-brand-dark">domyślnie zaznaczony</span>@endif
                        @if ($t->description)<p class="mt-0.5 text-xs text-muted">{{ $t->description }}</p>@endif</td>
                    <td class="px-3 py-2"><code class="text-xs">{{ $t->key }}</code></td>
                    <td class="min-w-[10rem] px-3 py-2 text-xs text-muted">{{ collect($t->feed_sources ?? [])->map(fn ($s) => \Modules\Newsletter\Services\ContentFeeder::SOURCES[$s] ?? $s)->implode(', ') ?: '—' }}@if ($t->news_category_slugs)<br>kategorie: {{ implode(', ', $t->news_category_slugs) }}@endif</td>
                    <td class="px-3 py-2 text-right"><a href="{{ route('admin.newsletter.subskrybenci.index', ['topic' => $t->key]) }}" class="hover:underline">{{ $counts[$t->key]['all'] }}</a></td>
                    <td class="px-3 py-2 text-right">{{ $counts[$t->key]['active'] }}</td>
                    <td class="px-3 py-2">{!! $t->is_active ? '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-900">aktywny</span>' : '<span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-800">ukryty</span>' !!}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap"><a href="{{ route('admin.newsletter.tematy.edit', $t) }}" class="text-muted hover:text-brand-dark" aria-label="Edytuj {{ $t->label }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-muted">Brak tematów.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($orphans)
        <div class="mt-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-bold">Klucze tematów u subskrybentów, których nie ma na liście:</p>
            <ul class="list-disc pl-5">@foreach ($orphans as $k => $n)<li><code>{{ $k }}</code> — {{ $n }} subskrybentów. Dodaj temat o tym kluczu, aby znów był widoczny, albo zostaw (nie przeszkadza w wysyłce).</li>@endforeach</ul>
        </div>
    @endif
    <p class="mt-4 text-xs text-muted">Gdzie tematy działają: formularze zapisu (wybór i domyślne zaznaczenie), preferencje subskrybenta, segmenty, odbiorcy kampanii („Tematy”), blok „Najnowsze aktualności” z opcją „dopasuj do tematów subskrybenta” (mapowanie kategorii → temat).</p>
    <script>
    (function(){
        const body = document.getElementById('topics-body'); if (!body) return;
        let dragging = null;
        body.querySelectorAll('tr[draggable]').forEach(tr => {
            tr.addEventListener('dragstart', e => { dragging = tr; tr.classList.add('opacity-50'); e.dataTransfer.effectAllowed = 'move'; });
            tr.addEventListener('dragend', () => { tr.classList.remove('opacity-50'); save(); });
            tr.addEventListener('dragover', e => { e.preventDefault(); if (!dragging || dragging === tr) return; const r = tr.getBoundingClientRect(); const after = e.clientY > r.top + r.height / 2; body.insertBefore(dragging, after ? tr.nextSibling : tr); });
            // klawiatura: Alt+↑/↓ na uchwycie
            tr.querySelector('[data-handle]')?.addEventListener('keydown', e => {
                if (!e.altKey || !['ArrowUp', 'ArrowDown'].includes(e.key)) return; e.preventDefault();
                const sib = e.key === 'ArrowUp' ? tr.previousElementSibling : tr.nextElementSibling; if (!sib) return;
                body.insertBefore(tr, e.key === 'ArrowUp' ? sib : sib.nextSibling); tr.querySelector('[data-handle]').focus(); save();
            });
        });
        function save(){ const ids = [...body.querySelectorAll('tr[data-id]')].map(tr => tr.dataset.id);
            fetch(body.dataset.reorderUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ ids }) }); }
    })();
    </script>
@endsection
