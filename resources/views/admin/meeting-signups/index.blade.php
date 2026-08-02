@extends('admin.layout')

@section('title', 'Zgłoszenia — „Daj znać, że przyjdziesz”')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Zgłoszenia z formularza „Daj znać, że przyjdziesz” (podstrona <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>): <span class="font-bold text-ink">{{ $total }}</span></p>
        @if ($total > 0)
            <a href="{{ route('admin.zgloszenia-spotkania.export') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-file-csv"></i> Eksport CSV
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Imię i nazwisko</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Termin</th>
                    <th class="px-4 py-3">Wiadomość</th>
                    <th class="px-4 py-3">Data zgłoszenia</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($signups as $signup)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $signup->name }}</td>
                        <td class="px-4 py-3"><a href="mailto:{{ $signup->email }}" class="text-brand hover:text-brand-dark">{{ $signup->email }}</a></td>
                        <td class="px-4 py-3 text-muted">{{ $signup->term ?: '—' }}</td>
                        <td class="max-w-xs px-4 py-3 text-muted">{{ $signup->message ? \Illuminate\Support\Str::limit($signup->message, 120) : '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $signup->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('admin.zgloszenia-spotkania.destroy', $signup) }}" onsubmit="return confirm('Usunąć zgłoszenie od &quot;{{ $signup->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Nikt jeszcze nie dał znać.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($signups->hasPages())
        <div class="mt-4">{{ $signups->links() }}</div>
    @endif
@endsection
