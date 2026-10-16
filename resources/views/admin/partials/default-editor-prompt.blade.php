{{-- Pytanie o domyślny edytor — po zalogowaniu, dopóki użytkownik go nie wybierze (Mój profil → Domyślny edytor).
     „Zdecyduję później” ukrywa okno do końca sesji. Natywny <dialog>: fokus w oknie, Esc = później. --}}
@php $promptUser = auth()->user(); @endphp
@if ($promptUser
    && ! session('default_editor_prompt_dismissed')
    && ! request()->routeIs('profile.edit')
    && $promptUser->needsDefaultEditorChoice())
    <dialog id="default-editor-prompt" aria-labelledby="default-editor-prompt-title" aria-describedby="default-editor-prompt-desc"
        class="m-auto overflow-hidden rounded-md bg-white p-0 text-ink backdrop:bg-black/50" style="width:min(36rem,calc(100vw - 2rem))">
        <form method="POST" action="{{ route('profile.editor') }}" class="p-6">
            @csrf
            @method('PATCH')
            <input type="hidden" name="from_prompt" value="1">

            <h2 id="default-editor-prompt-title" class="text-lg font-bold text-ink">Którego edytora chcesz używać?</h2>
            <p id="default-editor-prompt-desc" class="mt-1 text-sm text-ink">
                Wybrany edytor otworzy się po kliknięciu „Edytuj” przy stronie, aktualności i działaniu.
                Zmienisz to w każdej chwili w <a href="{{ route('profile.edit') }}#domyslny-edytor" class="font-bold underline">Mój profil</a>.
            </p>

            <fieldset class="mt-4">
                <legend class="sr-only">Domyślny edytor</legend>
                <div class="space-y-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-gray-50 p-3 hover:bg-brand-light has-[:checked]:bg-brand-light">
                        <input type="radio" name="default_editor" value="{{ \App\Models\User::EDITOR_CLASSIC }}" required
                            class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold text-ink">{{ \App\Models\User::EDITORS[\App\Models\User::EDITOR_CLASSIC] }}</span>
                            <span class="block text-sm text-ink">Formularz z polem treści i wszystkimi ustawieniami w jednym miejscu.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-gray-50 p-3 hover:bg-brand-light has-[:checked]:bg-brand-light">
                        <input type="radio" name="default_editor" value="{{ \App\Models\User::EDITOR_BLOCKS }}" required
                            class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold text-ink">{{ \App\Models\User::EDITORS[\App\Models\User::EDITOR_BLOCKS] }}</span>
                            <span class="block text-sm text-ink">Treść składana z bloków (nagłówki, tekst, zdjęcia, przyciski) z podglądem jak na stronie.</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                <button type="submit" form="default-editor-prompt-later"
                    class="min-h-10 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    Zdecyduję później
                </button>
                <button type="submit"
                    class="min-h-10 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    Zapisz wybór
                </button>
            </div>
        </form>
        <form id="default-editor-prompt-later" method="POST" action="{{ route('profile.editor.dismiss') }}" class="hidden">@csrf</form>
    </dialog>
    <script>
        (function () {
            var dialog = document.getElementById('default-editor-prompt');
            if (!dialog || typeof dialog.showModal !== 'function') return;
            dialog.showModal();
            // Esc = „Zdecyduję później” (zapamiętane w sesji, żeby okno nie wracało na każdej stronie).
            dialog.addEventListener('cancel', function (e) {
                e.preventDefault();
                document.getElementById('default-editor-prompt-later').submit();
            });
        })();
    </script>
@endif
