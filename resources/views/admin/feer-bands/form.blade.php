@extends('admin.layout')

@section('title', $band->exists ? 'Edytuj pasek' : 'Nowy pasek')

@section('content')
    @php $f = 'w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand'; @endphp
    <form method="POST" action="{{ $band->exists ? route('admin.feer-paski.update', $band) : route('admin.feer-paski.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($band->exists) @method('PUT') @endif

        <div>
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
            <input type="text" id="title" name="title" value="{{ old('title', $band->title) }}" required maxlength="160" class="{{ $f }}">
            @error('title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="text" class="mb-1 block text-sm font-bold">Tekst <span class="font-normal text-muted">(opcjonalnie, do 500 znaków)</span></label>
            <textarea id="text" name="text" rows="3" maxlength="500" class="{{ $f }}">{{ old('text', $band->text) }}</textarea>
            @error('text') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <fieldset class="grid gap-4 rounded-lg bg-gray-50 p-4 sm:grid-cols-2">
            <legend class="px-1 text-sm font-bold">Przyciski <span class="font-normal text-muted">(oba opcjonalne; etykietę i link podaj razem)</span></legend>
            @foreach ([['button_label', 'button_url', 'Przycisk główny'], ['button2_label', 'button2_url', 'Przycisk dodatkowy']] as [$lf, $uf, $legend])
                <div class="space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $legend }}</p>
                    <div>
                        <label for="{{ $lf }}" class="sr-only">Etykieta — {{ $legend }}</label>
                        <input type="text" id="{{ $lf }}" name="{{ $lf }}" value="{{ old($lf, $band->$lf) }}" placeholder="Etykieta, np. Wesprzyj nas" maxlength="80" class="{{ $f }}">
                        @error($lf) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="{{ $uf }}" class="sr-only">Link — {{ $legend }}</label>
                        <input type="text" id="{{ $uf }}" name="{{ $uf }}" value="{{ old($uf, $band->$uf) }}" placeholder="/wsparcie lub https://…" maxlength="255" class="{{ $f }}">
                        @error($uf) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach
        </fieldset>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="style" class="mb-1 block text-sm font-bold">Styl</label>
                <select id="style" name="style" class="{{ $f }}">
                    @foreach (\App\Models\FeerBand::STYLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('style', $band->style) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="placement" class="mb-1 block text-sm font-bold">Miejsce</label>
                <select id="placement" name="placement" class="{{ $f }}">
                    @foreach (\App\Models\FeerBand::PLACEMENTS as $value => $label)
                        <option value="{{ $value }}" @selected(old('placement', $band->placement) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $band->order ?? 0) }}" class="{{ $f }}">
                <p class="mt-1 text-xs text-muted">Przy kilku paskach w tym samym miejscu — rosnąco.</p>
            </div>
            <label class="flex items-center gap-2 self-end text-sm font-bold">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $band->is_active ?? true)) class="rounded border-gray-300 text-brand focus:ring-brand">
                Aktywny (widoczny na stronie)
            </label>
        </div>

        @if ($band->exists)
            <p class="rounded-lg bg-gray-50 p-3 text-sm text-ink">Skrót do wklejenia w treść dowolnej strony lub aktualności: <code class="rounded bg-white px-1 font-bold">{{ $band->shortcode() }}</code></p>
        @endif

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.feer-paski.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
