{{-- Typ „Zespół": karty osób (imię i nazwisko, rola, opis, e-mail). Dane: $page->typeData(). --}}
@php
    $td = $page->typeData();
    $members = collect($td['members'])->filter(fn ($m) => filled($m['name'] ?? null));
@endphp
@once
    <style>
        .tm-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
        .tm-card { display: flex; flex-direction: column; gap: .25rem; padding: 1.1rem 1.2rem; border: 2px solid #d1d5db; border-top: 6px solid var(--color-brand); border-radius: .5rem; background: #fff; }
        .tm-name { margin: 0; font-size: 1.15rem; font-weight: 800; color: #1d1d1a; } .tm-role { font-size: .95rem; font-weight: 700; color: var(--color-brand); }
        .tm-text { margin: .35rem 0 0; font-size: .95rem; line-height: 1.5; color: #1d1d1a; }
        .tm-mail { margin-top: auto; padding-top: .6rem; font-weight: 700; color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; overflow-wrap: anywhere; }
    </style>
@endonce
<section class="mx-auto max-w-5xl px-4 py-8">
    @include('page.partials.typed._head')
    @if ($members->isEmpty())
        <p class="text-muted">Lista osób zostanie wkrótce uzupełniona.</p>
    @else
        <ul role="list" class="tm-grid">
            @foreach ($members as $m)
                <li class="tm-card">
                    <h2 class="tm-name">{{ $m['name'] }}</h2>
                    @if (filled($m['role'] ?? null))<span class="tm-role">{{ $m['role'] }}</span>@endif
                    @if (filled($m['text'] ?? null))<p class="tm-text">{{ $m['text'] }}</p>@endif
                    @if (filled($m['email'] ?? null))<a class="tm-mail" href="mailto:{{ $m['email'] }}">{{ $m['email'] }}<span class="sr-only"> — napisz do: {{ $m['name'] }}</span></a>@endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
