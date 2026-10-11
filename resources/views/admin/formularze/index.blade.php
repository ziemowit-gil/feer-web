@extends('admin.layout')

@section('title', 'Kreator formularzy')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="Szukaj formularza…"
                class="rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            <button type="submit"
                class="rounded-md px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light text-ink">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="sr-only">Szukaj</span>
            </button>
        </form>
        <a href="{{ route('admin.formularze.create') }}"
            class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy formularz
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Formularz</th>
                    <th class="px-4 py-3">Identyfikator URL</th>
                    <th class="px-4 py-3 text-center">Pola</th>
                    <th class="px-4 py-3 text-center">Zgłoszenia</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($forms as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-ink">
                            <a href="{{ route('admin.formularze.edit', $item) }}"
                                class="hover:text-brand-dark focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                {{ $item->title }}
                            </a>
                            @if ($item->description)
                                <p class="mt-0.5 text-xs text-muted">{{ Str::limit($item->description, 80) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-muted">/formularz/{{ $item->slug }}</td>
                        <td class="px-4 py-3 text-center text-muted">{{ count($item->fields ?? []) }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.formularze.zgloszenia', $item) }}"
                                class="inline-flex items-center gap-1 rounded hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                {{ $item->submissions_count }}
                                @if ($item->unread_submissions_count > 0)
                                    <span class="rounded-full bg-brand-dark px-1.5 py-0.5 text-[10px] font-bold text-white"
                                        aria-label="{{ $item->unread_submissions_count }} nowych">
                                        {{ $item->unread_submissions_count }}
                                    </span>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->is_active)
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Aktywny</span>
                            @else
                                <span class="text-xs font-bold text-muted">Nieaktywny</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('formularz.show', $item->slug) }}" target="_blank" rel="noopener"
                                    class="rounded p-1.5 text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    title="Podgląd formularza">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('admin.formularze.edit', $item) }}"
                                    class="rounded p-1.5 text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    title="Edytuj formularz">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.formularze.destroy', $item) }}"
                                    onsubmit="return confirm('Usunąć formularz „{{ addslashes($item->title) }}” wraz ze wszystkimi zgłoszeniami?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="rounded p-1.5 text-muted hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                        title="Usuń formularz">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted">
                            <i class="fa-solid fa-wpforms mb-2 text-3xl text-gray-300" aria-hidden="true"></i>
                            <p class="text-sm">Brak formularzy.
                                <a href="{{ route('admin.formularze.create') }}" class="text-brand-dark hover:underline">Utwórz pierwszy</a>.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($forms->hasPages())
        <div class="mt-4">{{ $forms->links() }}</div>
    @endif
@endsection
