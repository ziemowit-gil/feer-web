@extends('admin.layout')

@section('title', 'Wybór witryny')

@section('content')
    <div class="mx-auto max-w-4xl">
        <h2 class="text-2xl font-extrabold text-ink">Nad którą witryną chcesz dziś pracować?</h2>
        <p class="mt-2 text-sm text-ink">Wybór zmienia, której witryny dotyczą podstrony, aktualności i ustawienia w panelu. Możesz go zmienić w każdej chwili w pasku bocznym.</p>

        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($sites as $site)
                @php($name = $site->site_name ?: ($site->slug ?: 'Witryna '.$site->id))
                <li>
                    <form method="POST" action="{{ route('admin.wybor-witryny.choose', $site) }}">
                        @csrf
                        <button type="submit" class="flex min-h-20 w-full items-center gap-4 rounded-md bg-gray-100 p-4 text-left hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-light text-brand-dark"><i class="fa-solid {{ $site->parent_site_id ? 'fa-sitemap' : 'fa-house' }}" aria-hidden="true"></i></span>
                            <span class="min-w-0">
                                <span class="block font-bold text-ink">{{ $name }}</span>
                                <span class="block truncate text-sm text-ink">{{ $site->parent_site_id ? 'Witryna w sieci' : 'Witryna główna' }}@if ($site->domain) · {{ $site->domain }}@endif @if ($site->id === $current->id)· <strong>ostatnio wybrana</strong>@endif</span>
                            </span>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>

        <p class="mt-6 text-sm">
            <a href="{{ route('admin.witryny.ustawienia') }}" class="font-bold text-brand-dark underline hover:text-ink"><i class="fa-solid fa-copy" aria-hidden="true"></i> Przenieś ustawienia między witrynami (nagłówek, kolory, moduły)</a>
        </p>
    </div>
@endsection
