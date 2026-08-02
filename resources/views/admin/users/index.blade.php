@extends('admin.layout')

@section('title', 'Użytkownicy')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.uzytkownicy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj użytkownika
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Rola</th>
                    <th class="px-4 py-3">Grupa</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $user->name }}
                            @if ($user->microsoft_id)
                                <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-[#f3f6fb] px-2 py-0.5 align-middle text-[11px] font-bold text-[#0067b8]" title="Konto połączone z Microsoft 365">
                                    <i class="fa-brands fa-microsoft"></i> Microsoft 365
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $user->isAdmin() ? 'bg-brand-light text-brand' : 'bg-gray-100 text-muted' }}">
                                {{ $user->isAdmin() ? 'Administrator' : 'Edytor' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $user->isAdmin() ? '—' : ($user->group->name ?? 'brak') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.uzytkownicy.edit', $user) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                @if ($user->microsoft_id)
                                    <form method="POST" action="{{ route('admin.uzytkownicy.microsoft.unlink', $user) }}" onsubmit="return confirm('Odłączyć konto Microsoft 365 od użytkownika &quot;{{ $user->name }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-amber-600" title="Odłącz konto Microsoft 365"><i class="fa-solid fa-link-slash"></i></button>
                                    </form>
                                @endif
                                @unless (auth()->user()->is($user))
                                    <form method="POST" action="{{ route('admin.uzytkownicy.destroy', $user) }}" onsubmit="return confirm('Usunąć użytkownika &quot;{{ $user->name }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak użytkowników.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
