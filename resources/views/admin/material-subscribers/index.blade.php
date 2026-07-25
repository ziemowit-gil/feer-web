@extends('admin.layout')

@section('title', 'Zapisy — nowe materiały')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Adresy zapisane na powiadomienia o nowych materiałach: <span class="font-bold text-ink">{{ $total }}</span></p>
        @if ($total > 0)
            <a href="{{ route('admin.zapisy-materialy.export') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-file-csv"></i> Eksport CSV
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Adres e-mail</th>
                    <th class="px-4 py-3">Data zapisu</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($subscribers as $subscriber)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3 text-muted">{{ $subscriber->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('admin.zapisy-materialy.destroy', $subscriber) }}" onsubmit="return confirm('Usunąć adres &quot;{{ $subscriber->email }}&quot; z listy?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-muted">Nikt jeszcze się nie zapisał.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($subscribers->hasPages())
        <div class="mt-4">{{ $subscribers->links() }}</div>
    @endif
@endsection
