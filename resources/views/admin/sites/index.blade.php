@extends('admin.layout')

@section('title', 'Witryny sieci')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="max-w-2xl text-sm text-muted">
            Główna witryna i jej sub-witryny (np. „Ośrodek" albo osobny BIP) — każda ma własną nazwę, branding i treść, ale korzysta
            z tego samego szablonu. Przełącz się na wybraną witrynę w pasku bocznym, żeby edytować jej ustawienia
            i treść w dotychczasowych ekranach panelu.
        </p>
        <a href="{{ route('admin.witryny.create') }}" class="shrink-0 rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa sub-witryna
        </a>
    </div>

    <div class="space-y-3">
        @foreach ($sites->whereNull('parent_site_id') as $site)
            <div class="overflow-hidden rounded-md bg-white">
                <div class="flex items-center gap-3 px-4 py-3">
                    @if ($site->logoUrl())
                        <img src="{{ $site->logoUrl() }}" alt="" class="h-9 w-9 flex-none rounded object-contain">
                    @else
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-brand-dark text-sm font-bold text-white">{{ mb_substr($site->site_name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink">{{ $site->site_name }}
                            <span class="ml-1 text-[10px] font-bold uppercase tracking-wide text-muted">Główna witryna</span>
                        </p>
                        <p class="truncate text-xs text-muted">{{ $site->domain ?: url('/') }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.witryny.przelacz', $site) }}">
                        @csrf
                        <button type="submit" class="rounded-md px-3 py-1.5 text-xs font-bold text-muted hover:text-brand-dark bg-gray-100 hover:bg-brand-light">Przełącz się</button>
                    </form>
                </div>

                @if ($site->subsites->isNotEmpty())
                    <div class="divide-y divide-gray-100 border-t border-gray-100 bg-gray-50">
                        @foreach ($site->subsites as $sub)
                            <div class="flex items-center gap-3 px-4 py-3 pl-10">
                                <i class="fa-solid fa-arrow-turn-up fa-rotate-90 text-xs text-gray-300" aria-hidden="true"></i>
                                @if ($sub->logoUrl())
                                    <img src="{{ $sub->logoUrl() }}" alt="" class="h-8 w-8 flex-none rounded object-contain">
                                @else
                                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded bg-gray-200 text-xs font-bold text-muted">{{ mb_substr($sub->site_name, 0, 1) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-ink">{{ $sub->site_name }}
                                        @if ($sub->isBipSite())
                                            <span class="ml-1 text-[10px] font-bold uppercase tracking-wide text-muted">BIP</span>
                                        @endif
                                    </p>
                                    <p class="truncate text-xs text-muted">
                                        @if ($sub->domain || $sub->slug)
                                            {{ current_site_url($sub) }}
                                        @else
                                            <span class="italic">Brak domeny/sluga — witryna nieosiągalna publicznie</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <form method="POST" action="{{ route('admin.witryny.przelacz', $sub) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md px-3 py-1.5 text-xs font-bold text-muted hover:text-brand-dark bg-white hover:bg-brand-light">Przełącz się</button>
                                    </form>
                                    <a href="{{ route('admin.witryny.edit', $sub) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.witryny.confirm-destroy', $sub) }}" class="inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń witrynę {{ $sub->site_name }}</span></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Kosz witryn: przywrócenie albo trwałe usunięcie; po SiteSetting::TRASH_DAYS dniach usuwa je witryny:oproznij-kosz. --}}
    @if ($trashed->isNotEmpty())
        <section aria-labelledby="h-site-trash" class="mt-8">
            <h2 id="h-site-trash" class="text-base font-bold text-ink">Kosz</h2>
            <p class="mt-1 text-sm text-muted">Witryny w koszu są niedostępne publicznie, ale ich treść jest zachowana. Po {{ \App\Models\SiteSetting::TRASH_DAYS }} dniach zostaną usunięte trwale.</p>
            <ul class="mt-3 divide-y divide-gray-100 overflow-hidden rounded-md bg-white" role="list">
                @foreach ($trashed as $t)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-ink">{{ $t->site_name }}</p>
                            <p class="text-xs text-muted">W koszu od {{ \Illuminate\Support\Carbon::parse($t->trashed_at)->format('d.m.Y H:i') }} · zostanie usunięta trwale {{ $t->purgeAt()->format('d.m.Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.witryny.restore', $t) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć<span class="sr-only"> witrynę {{ $t->site_name }}</span></button>
                        </form>
                        <a href="{{ route('admin.witryny.confirm-destroy', $t) }}" class="inline-flex min-h-11 items-center gap-2 rounded-md px-4 text-sm font-bold text-red-800 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń trwale teraz<span class="sr-only"> — {{ $t->site_name }}</span></a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
