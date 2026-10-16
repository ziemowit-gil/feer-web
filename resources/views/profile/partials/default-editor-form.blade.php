{{-- Domyślny edytor treści: co otwiera „Edytuj” przy stronie, aktualności i działaniu. --}}
@php
    $current = old('default_editor', auth()->user()->default_editor);
    $editorHints = [
        \App\Models\User::EDITOR_CLASSIC => 'Formularz z polem treści i wszystkimi ustawieniami w jednym miejscu.',
        \App\Models\User::EDITOR_BLOCKS => 'Treść składana z bloków (nagłówki, tekst, zdjęcia, przyciski) z podglądem jak na stronie.',
    ];
@endphp
<form method="POST" action="{{ route('profile.editor') }}" class="space-y-3">
    @csrf
    @method('PATCH')

    <fieldset>
        <legend class="text-sm font-bold text-ink">Co ma się otwierać po kliknięciu „Edytuj”?</legend>
        @if (blank($current))
            <p class="mt-1 text-sm text-muted">Nie wybrano jeszcze domyślnego edytora — panel zapyta o to po zalogowaniu.</p>
        @endif
        <div class="mt-3 space-y-2">
            @foreach (\App\Models\User::EDITORS as $value => $label)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-gray-50 p-3 transition-colors hover:bg-brand-light has-[:checked]:bg-brand-light">
                    <input type="radio" name="default_editor" value="{{ $value }}" @checked($current === $value) required
                        aria-describedby="default-editor-hint-{{ $value }}"
                        class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                    <span>
                        <span class="block text-sm font-bold text-ink">{{ $label }}</span>
                        <span id="default-editor-hint-{{ $value }}" class="block text-sm text-ink">{{ $editorHints[$value] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>
    @error('default_editor') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

    <p class="text-sm text-muted">Drugi edytor zawsze zostaje pod ręką — przyciskiem w formularzu lub w edytorze blokowym.</p>

    <button type="submit"
        class="rounded-lg bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
        Zapisz domyślny edytor
    </button>
</form>
