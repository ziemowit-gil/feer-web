@extends('admin.layout')
@section('title', 'Szablony newslettera')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-muted">Szablony startowe kampanii: układ z edytora Mosaico (brandbook FEER) albo własny HTML.</p>
        <a href="{{ route('admin.newsletter.szablony.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy szablon</a>
    </div>
    <section class="mb-6 p-4 rounded-md bg-white" aria-labelledby="sys-h">
        <h2 id="sys-h" class="mb-1 text-sm font-bold uppercase text-muted">Maile systemowe</h2>
        <p class="mb-3 text-xs text-muted">Wiadomości wysyłane automatycznie. Możesz je przeprojektować w Mosaico (tagi: <code>@{{confirm_url}}</code>, <code>@{{first_name}}</code>…) albo zostawić wbudowany wygląd.</p>
        <ul class="divide-y divide-gray-100">
            @foreach ($systemMails as $m)
                <li class="flex flex-wrap items-center gap-3 py-3">
                    <div class="grow"><p class="font-bold text-ink">{{ $m['name'] }}</p>
                        <p class="text-xs text-muted">{{ $m['template'] && $m['template']->html_body ? 'Własny projekt z Mosaico' . (str_contains((string) $m['template']->html_body, $m['required']) ? '' : ' — ⚠ brak tagu ' . '{'.'{' . $m['required'] . '}'.'}' . ', wysyłany jest wbudowany') : 'Wbudowany wygląd' }}{{ $m['template']?->subject ? ' · temat: „' . $m['template']->subject . '”' : '' }}</p></div>
                    <a href="{{ route('admin.newsletter.szablony.system', $m['key']) }}" class="rounded bg-brand-dark px-3 py-1.5 text-sm font-bold text-white hover:bg-ink">{{ $m['template'] ? 'Edytuj w Mosaico' : 'Zaprojektuj w Mosaico' }}</a>
                    @if ($m['template'])
                        <form method="POST" action="{{ route('admin.newsletter.szablony.destroy', $m['template']) }}" onsubmit="return confirm('Przywrócić wbudowany wygląd? Projekt z Mosaico zostanie usunięty.')">@csrf @method('DELETE')<button class="rounded-md px-3 py-1.5 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Przywróć wbudowany</button></form>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
    <h2 class="mb-2 text-sm font-bold uppercase text-muted">Szablony kampanii</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($templates as $t)
            <article class="p-4 rounded-md bg-white">
                <h2 class="font-bold text-ink">{{ $t->name }} @if($t->is_default)<span class="ml-1 text-xs text-brand-dark font-bold">domyślny</span>@endif</h2>
                <p class="text-xs text-muted">{{ \Modules\Newsletter\Models\NewsletterTemplate::KINDS[$t->kind] ?? $t->kind }}{{ $t->mosaico_template ? ' · ' . $t->mosaico_template : '' }} · {{ $t->html_body ? round(strlen($t->html_body) / 1024) . ' kB' : 'pusty' }}</p>
                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                    @if ($t->kind === 'mosaico')<a href="{{ route('admin.newsletter.szablony.editor', $t) }}" class="rounded bg-brand-dark px-3 py-1.5 font-bold text-white hover:bg-ink">Edytor</a>@endif
                    <a href="{{ route('admin.newsletter.szablony.edit', $t) }}" class="rounded-md px-3 py-1.5 font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Ustawienia</a>
                    <form method="POST" action="{{ route('admin.newsletter.szablony.duplicate', $t) }}">@csrf<button class="rounded-md px-3 py-1.5 font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Duplikuj</button></form>
                    <form method="POST" action="{{ route('admin.newsletter.szablony.destroy', $t) }}" onsubmit="return confirm('Usunąć szablon?')">@csrf @method('DELETE')<button class="rounded-md px-3 py-1.5 font-bold text-red-800 bg-red-50 hover:bg-red-100">Usuń</button></form>
                </div>
            </article>
        @empty
            <p class="text-sm text-muted">Brak szablonów — utwórz pierwszy, otworzy się edytor Mosaico z szablonem FEER.</p>
        @endforelse
    </div>
@endsection
