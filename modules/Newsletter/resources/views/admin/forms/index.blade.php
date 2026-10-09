@extends('admin.layout')
@section('title', 'Formularze zapisu')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-muted">Dedykowane formularze zapisu: treści, pola, tematy, zgoda, listy docelowe i podpięcie do SZO. Domyślny formularz wyświetla się na stronie <a href="{{ route('newsletter.show') }}" target="_blank" rel="noopener" class="font-bold text-brand-dark underline">/newsletter</a> i w sekcji newslettera na stronie głównej.</p>
        <a href="{{ route('admin.newsletter.formularze.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy formularz</a>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-3">Nazwa</th><th class="px-4 py-3">Nagłówek</th><th class="px-4 py-3">Adres</th><th class="px-4 py-3 text-right">Zgłoszeń</th><th class="px-4 py-3">Stan</th><th class="px-4 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody>
            @forelse ($forms as $f)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2 font-bold">{{ $f->name }} @if($f->is_default)<span class="ml-1 rounded-full bg-brand/10 px-2 py-0.5 text-xs text-brand-dark">domyślny</span>@endif</td>
                    <td class="px-4 py-2">{{ $f->heading }}</td>
                    <td class="px-4 py-2 text-xs"><a href="{{ route('newsletter.form', $f) }}" target="_blank" rel="noopener" class="text-brand-dark underline">/newsletter/{{ $f->slug }}</a></td>
                    <td class="px-4 py-2 text-right">{{ $f->submissions_count }}</td>
                    <td class="px-4 py-2">{{ $f->is_active ? 'aktywny' : 'wyłączony' }}</td>
                    <td class="px-4 py-2 text-right whitespace-nowrap"><a href="{{ route('admin.newsletter.formularze.edit', $f) }}" class="mr-3 text-muted hover:text-brand-dark" aria-label="Edytuj {{ $f->name }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                        <form method="POST" action="{{ route('admin.newsletter.formularze.destroy', $f) }}" class="inline" onsubmit="return confirm('Usunąć formularz?')">@csrf @method('DELETE')<button class="text-muted hover:text-red-700" aria-label="Usuń {{ $f->name }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">Brak formularzy — utwórz pierwszy, żeby włączyć zapisy.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs text-muted">Osadzenie w szablonie Blade: <code>&lt;x-newsletter-widget :form="$form" style="card" source="stopka" /&gt;</code> (bez <code>:form</code> użyje domyślnego).</p>
@endsection
