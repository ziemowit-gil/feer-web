@extends('admin.layout')

@section('title', 'Partnerzy')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.partnerzy.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj partnera
        </a>
    </div>

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Logo</th>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Link</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($partners as $partner)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="" class="h-10 w-16 rounded border border-gray-100 object-contain p-1">
                            @else
                                <span class="flex h-10 w-16 items-center justify-center rounded border border-dashed border-gray-200 text-xs text-muted">Brak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $partner->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $partner->url ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $partner->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.partnerzy.edit', $partner) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.partnerzy.destroy', $partner) }}" onsubmit="return confirm('Usunąć partnera &quot;{{ $partner->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                        @include('admin.partials.empty-state', ['icon' => 'fa-handshake', 'title' => 'Nie ma jeszcze partnerów', 'text' => 'Logotypy partnerów i sponsorów pokażą się na stronie w sekcji partnerów.', 'route' => 'admin.partnerzy.create', 'action' => 'Dodaj partnera', 'colspan' => 5])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
