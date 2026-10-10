@extends('admin.layout')
@section('title', 'Listy mailingowe')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Listy statyczne: dodajesz członków ręcznie, z segmentu, po temacie lub przez formularz zapisu. <a href="{{ route('admin.newsletter.segmenty.index') }}" class="font-bold text-brand-dark underline">Segmenty dynamiczne →</a></p>
        <a href="{{ route('admin.newsletter.listy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa lista</a>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-3">Nazwa</th><th class="px-4 py-3">Opis</th><th class="px-4 py-3 text-right">Członków</th><th class="px-4 py-3 text-right">Aktywnych</th><th class="px-4 py-3">Publiczna</th><th class="px-4 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody>
            @forelse ($lists as $l)
                <tr class="border-t border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-2 font-bold"><a href="{{ route('admin.newsletter.listy.show', $l) }}" class="text-brand-dark hover:underline">{{ $l->name }}</a> <span class="text-xs font-normal text-muted">{{ $l->slug }}</span></td>
                    <td class="px-4 py-2 text-muted">{{ \Illuminate\Support\Str::limit($l->description, 80) }}</td>
                    <td class="px-4 py-2 text-right">{{ $l->subscribers_count }}</td>
                    <td class="px-4 py-2 text-right">{{ $l->active_count }}</td>
                    <td class="px-4 py-2">{{ $l->is_public ? 'tak' : 'nie' }}</td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        <a href="{{ route('admin.newsletter.listy.show', $l) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Członkowie listy {{ $l->name }}"><i class="fa-solid fa-users" aria-hidden="true"></i></a>
                        <a href="{{ route('admin.newsletter.listy.export', $l) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Eksport CSV listy {{ $l->name }}"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
                        <a href="{{ route('admin.newsletter.listy.edit', $l) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Edytuj {{ $l->name }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                        <form method="POST" action="{{ route('admin.newsletter.listy.destroy', $l) }}" class="inline" onsubmit="return confirm('Usunąć listę? Subskrybenci pozostaną w bazie.')">@csrf @method('DELETE')<button class="text-muted hover:text-red-700" aria-label="Usuń {{ $l->name }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">Brak list — utwórz pierwszą.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
