@extends('admin.layout')

@section('title', 'Szablony maili')

@section('content')
    <div class="mb-4">
        <p class="text-sm text-muted">Edytuj temat i treść wiadomości e-mail wysyłanych automatycznie przez system. Zmienne w postaci <code class="rounded bg-gray-100 px-1">@{{zmienna}}</code> zostaną zastąpione rzeczywistymi wartościami.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa szablonu</th>
                    <th class="px-4 py-3">Klucz</th>
                    <th class="px-4 py-3">Temat</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($templates as $template)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $template->name }}</td>
                        <td class="px-4 py-3">
                            <code class="rounded bg-gray-100 px-1 text-xs text-muted">{{ $template->slug }}</code>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $template->subject }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.mail-templates.edit', $template) }}"
                               class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50"
                               aria-label="Edytuj szablon {{ $template->name }}">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-muted">
                            Brak szablonów. Uruchom <code>php artisan db:seed --class=MailTemplateSeeder</code> aby dodać domyślne.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
