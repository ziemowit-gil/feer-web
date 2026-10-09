@extends('admin.layout')
@section('title', 'Listy subskrybentów')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-muted">Listy statyczne — przypisujesz ręcznie lub przez formularz zapisu. <a href="{{ route('admin.newsletter.segmenty.index') }}" class="font-bold text-brand-dark underline">Segmenty dynamiczne →</a></p>
        <a href="{{ route('admin.newsletter.listy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa lista</a>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-3">Nazwa</th><th class="px-4 py-3">Opis</th><th class="px-4 py-3 text-right">Subskrybenci</th><th class="px-4 py-3">Publiczna</th><th class="px-4 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody>
            @forelse ($lists as $l)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2 font-bold"><a href="{{ route('admin.newsletter.subskrybenci.index', ['list' => $l->id]) }}" class="text-brand-dark hover:underline">{{ $l->name }}</a> <span class="text-xs font-normal text-muted">{{ $l->slug }}</span></td>
                    <td class="px-4 py-2 text-muted">{{ $l->description }}</td>
                    <td class="px-4 py-2 text-right">{{ $l->subscribers_count }}</td>
                    <td class="px-4 py-2">{{ $l->is_public ? 'tak (w preferencjach)' : 'nie' }}</td>
                    <td class="px-4 py-2 text-right whitespace-nowrap"><a href="{{ route('admin.newsletter.listy.edit', $l) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Edytuj {{ $l->name }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                        <form method="POST" action="{{ route('admin.newsletter.listy.destroy', $l) }}" class="inline" onsubmit="return confirm('Usunąć listę? Subskrybenci pozostaną w bazie.')">@csrf @method('DELETE')<button class="text-muted hover:text-red-700" aria-label="Usuń {{ $l->name }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">Brak list.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
