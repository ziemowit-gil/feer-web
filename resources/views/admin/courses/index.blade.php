@extends('admin.layout')

@section('title', 'Oferta szkoleń i zajęć')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Szkolenia z terminami i zapisami (strona <a href="{{ route('courses.index') }}" target="_blank" rel="noopener" class="text-brand-dark underline">/zajecia</a>).</p>
        <a href="{{ route('admin.kursy.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj szkolenie
        </a>
    </div>

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Forma</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Terminy</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($courses as $c)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $c->title }}@if ($c->is_featured) <span class="text-xs font-bold text-muted">· wyróżnione</span>@endif</td>
                        <td class="px-4 py-3 text-muted">{{ $c->categoryLabel() }}</td>
                        <td class="px-4 py-3 text-muted">{{ $c->formatLabel() }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-ink">{{ $c->statusLabel() }}</td>
                        <td class="px-4 py-3 text-muted">{{ $c->editions_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.kursy.edit', $c) }}" class="text-brand-dark hover:text-ink" title="Edytuj i terminy" aria-label="Edytuj {{ $c->title }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.kursy.destroy', $c) }}" onsubmit="return confirm('Usunąć szkolenie razem z terminami i zapisami?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" aria-label="Usuń {{ $c->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('admin.partials.empty-state', ['icon' => 'fa-chalkboard-user', 'title' => 'Nie ma jeszcze szkoleń', 'text' => 'Dodaj szkolenie, potem jego terminy. Zapisy pojawią się na stronie oferty.', 'route' => 'admin.kursy.create', 'action' => 'Dodaj szkolenie', 'colspan' => 6])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
