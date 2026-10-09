@extends('admin.layout')
@section('title', 'Segmenty dynamiczne')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-muted">Segmenty wyliczane w chwili wysyłki z reguł (status, tematy, aktywność, listy, ośrodek…).</p>
        <a href="{{ route('admin.newsletter.segmenty.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy segment</a>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-3">Nazwa</th><th class="px-4 py-3">Reguły</th><th class="px-4 py-3 text-right">Liczność</th><th class="px-4 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody>
            @forelse ($segments as $s)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2 font-bold"><a href="{{ route('admin.newsletter.subskrybenci.index', ['segment' => $s->id]) }}" class="text-brand-dark hover:underline">{{ $s->name }}</a></td>
                    <td class="px-4 py-2 text-xs text-muted">{{ strtoupper($s->rules['match'] ?? 'all') === 'ANY' ? 'dowolna z' : 'wszystkie' }}: {{ collect($s->rules['rules'] ?? [])->map(fn ($r) => $r['field'] . ' ' . $r['op'] . ' ' . (is_array($r['value'] ?? null) ? implode('|', $r['value']) : ($r['value'] ?? '')))->implode('; ') }}</td>
                    <td class="px-4 py-2 text-right">{{ $s->cached_count ?? '—' }} <span class="text-xs text-muted">{{ $s->counted_at?->format('d.m H:i') }}</span></td>
                    <td class="px-4 py-2 text-right whitespace-nowrap"><a href="{{ route('admin.newsletter.segmenty.edit', $s) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Edytuj {{ $s->name }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                        <form method="POST" action="{{ route('admin.newsletter.segmenty.destroy', $s) }}" class="inline" onsubmit="return confirm('Usunąć segment?')">@csrf @method('DELETE')<button class="text-muted hover:text-red-700" aria-label="Usuń {{ $s->name }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">Brak segmentów.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
