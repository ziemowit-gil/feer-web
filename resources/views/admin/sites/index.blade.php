@extends('admin.layout')

@section('title', 'Witryny sieci')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="max-w-2xl text-sm text-muted">
            Główna witryna i jej sub-witryny (np. „Ośrodek") — każda ma własną nazwę, branding i treść, ale korzysta
            z tego samego szablonu. Przełącz się na wybraną witrynę w pasku bocznym, żeby edytować jej ustawienia
            i treść w dotychczasowych ekranach panelu.
        </p>
        <a href="{{ route('admin.witryny.create') }}" class="shrink-0 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa sub-witryna
        </a>
    </div>

    <div class="space-y-3">
        @foreach ($sites->whereNull('parent_site_id') as $site)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="flex items-center gap-3 px-4 py-3">
                    @if ($site->logoUrl())
                        <img src="{{ $site->logoUrl() }}" alt="" class="h-9 w-9 flex-none rounded object-contain">
                    @else
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-brand text-sm font-bold text-white">{{ mb_substr($site->site_name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink">{{ $site->site_name }}
                            <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-muted">Główna witryna</span>
                        </p>
                        <p class="truncate text-xs text-muted">{{ $site->domain ?: url('/') }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.witryny.przelacz', $site) }}">
                        @csrf
                        <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-brand hover:text-brand">Przełącz się</button>
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
                                    <p class="truncate text-sm font-bold text-ink">{{ $sub->site_name }}</p>
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
                                        <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-brand hover:text-brand">Przełącz się</button>
                                    </form>
                                    <a href="{{ route('admin.witryny.edit', $sub) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.witryny.destroy', $sub) }}" onsubmit="return confirm('Usunąć witrynę „{{ $sub->site_name }}”?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
