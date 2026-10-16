@extends('admin.layout')

@section('title', 'Slider: '.$slider->name)

@section('content')
    @php $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold'; @endphp

    <nav class="mb-4 text-sm" aria-label="Ścieżka">
        <a href="{{ route('admin.hero.index') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Slidery</a>
        <span aria-hidden="true" class="mx-1 text-muted">/</span>
        <span aria-current="page" class="text-ink">{{ $slider->name }}</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start">
        {{-- Slajdy --}}
        <div class="min-w-0 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-ink">{{ $slider->name }}</h1>
                <a href="{{ route('admin.hero.create', ['slider' => $slider->id]) }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj slajd
                </a>
            </div>

            @if ($slider->is_home)
                @include('admin.hero.partials.mission-panel', ['slideCount' => $slider->slides->count()])
            @endif

            @if ($slider->slides->isEmpty())
                <div class="rounded-md bg-white p-10 text-center">
                    <p class="font-bold text-ink">Ten slider nie ma jeszcze slajdów.</p>
                    <a href="{{ route('admin.hero.create', ['slider' => $slider->id]) }}" class="{{ $btn }} mt-4 bg-brand-dark text-white hover:bg-ink">Dodaj pierwszy slajd</a>
                </div>
            @else
                <ol class="space-y-3" role="list">
                    @foreach ($slider->slides as $slide)
                        <li class="flex flex-wrap items-center gap-4 rounded-md bg-white p-3">
                            <span class="flex h-20 w-32 flex-none items-center justify-center overflow-hidden rounded bg-gray-100">
                                @if ($slide->image_url)<img src="{{ $slide->image_url }}" alt="" class="h-full w-full object-cover" loading="lazy">@else<i class="fa-solid fa-image text-gray-400" aria-hidden="true"></i>@endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-widest text-muted">Slajd {{ $loop->iteration }}</p>
                                <h2 class="truncate font-bold text-ink">{{ $slide->title }}</h2>
                                @if ($slide->text)<p class="line-clamp-2 text-sm text-ink">{{ $slide->text }}</p>@endif
                                @if ($slideState = $slide->scheduleState())
                                    <p class="mt-1 text-xs font-bold {{ $slideState === 'active' ? 'text-ink' : 'text-muted' }}">
                                        <i class="fa-solid {{ ['scheduled' => 'fa-clock', 'active' => 'fa-calendar-check', 'expired' => 'fa-calendar-xmark'][$slideState] }} mr-1 text-brand-dark" aria-hidden="true"></i>
                                        {{ ['scheduled' => 'Zaplanowany', 'active' => 'Widoczny', 'expired' => 'Zakończony — niewidoczny'][$slideState] }}:
                                        <span class="font-normal">{{ $slide->starts_at ? 'od '.$slide->starts_at->format('d.m.Y H:i') : '' }}{{ $slide->starts_at && $slide->ends_at ? ' ' : '' }}{{ $slide->ends_at ? 'do '.$slide->ends_at->format('d.m.Y H:i') : '' }}</span>
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-none flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('admin.slidery.move', [$slider, $slide]) }}">
                                    @csrf
                                    <input type="hidden" name="kierunek" value="gora">
                                    <button type="submit" @disabled($loop->first) class="rounded-md bg-gray-100 p-2.5 text-ink hover:bg-brand-light disabled:opacity-40" aria-label="Przesuń wyżej: {{ $slide->title }}"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.slidery.move', [$slider, $slide]) }}">
                                    @csrf
                                    <input type="hidden" name="kierunek" value="dol">
                                    <button type="submit" @disabled($loop->last) class="rounded-md bg-gray-100 p-2.5 text-ink hover:bg-brand-light disabled:opacity-40" aria-label="Przesuń niżej: {{ $slide->title }}"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                </form>
                                <a href="{{ route('admin.hero.edit', $slide) }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Edytuj<span class="sr-only"> slajd {{ $slide->title }}</span></a>
                                <form method="POST" action="{{ route('admin.hero.destroy', $slide) }}" onsubmit="return confirm(@js('Usunąć slajd „'.$slide->title.'”?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $btn }} bg-white text-red-800 hover:bg-red-50">Usuń<span class="sr-only"> slajd {{ $slide->title }}</span></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        {{-- Ustawienia slidera --}}
        <aside class="space-y-4 lg:sticky lg:top-4" aria-label="Ustawienia slidera">
            <form method="POST" action="{{ route('admin.slidery.update', $slider) }}" class="space-y-4 rounded-md bg-white p-5">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-bold uppercase tracking-widest text-muted">Ustawienia</h2>
                <div>
                    <label for="slider-name" class="mb-1 block text-sm font-bold text-ink">Nazwa</label>
                    <input id="slider-name" name="name" type="text" required maxlength="120" value="{{ old('name', $slider->name) }}"
                        class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                </div>
                <fieldset>
                    <legend class="mb-2 text-sm font-bold text-ink">Gdzie pokazywać</legend>
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="is_home" value="1" @checked($slider->is_home) class="mt-0.5 h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Strona główna
                    </label>
                    <label class="mt-2 flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="show_on_blog" value="1" @checked($slider->show_on_blog) class="mt-0.5 h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Blog (lista wpisów)
                    </label>
                    <p class="mt-2 text-xs text-muted">Strona główna i blog mają po jednym sliderze — zaznaczenie tutaj zdejmuje je z innego.</p>
                </fieldset>
                <div>
                    <p class="text-sm font-bold text-ink">Na stronach</p>
                    @if ($pages->isEmpty())
                        <p class="text-sm text-ink">Żadna strona nie używa tego slidera. Wybierz go w edycji strony → Typ i układ → „Slider nad treścią”.</p>
                    @else
                        <ul class="mt-1 space-y-0.5 text-sm" role="list">
                            @foreach ($pages as $p)
                                <li><a href="{{ route('admin.podstrony.edit', $p->id) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $p->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <label for="slider-duration" class="mb-1 block text-sm font-bold text-ink">Czas jednego slajdu <span class="font-normal text-muted">(sekundy)</span></label>
                    <input id="slider-duration" name="duration" type="number" min="3" max="60" value="{{ old('duration', $slider->duration) }}" placeholder="6"
                        class="w-28 rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                </div>
                <button type="submit" class="{{ $btn }} w-full justify-center bg-brand-dark text-white hover:bg-ink">Zapisz ustawienia</button>
            </form>

            <form method="POST" action="{{ route('admin.slidery.destroy', $slider) }}" onsubmit="return confirm(@js('Usunąć slider „'.$slider->name.'” razem ze slajdami ('.$slider->slides->count().')?'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="{{ $btn }} w-full justify-center bg-white text-red-800 hover:bg-red-50"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń slider</button>
            </form>
        </aside>
    </div>
@endsection
