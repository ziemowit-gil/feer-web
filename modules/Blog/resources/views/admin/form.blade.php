@extends('admin.layout')

@section('title', $article->exists ? 'Edytuj artykuł' : 'Nowy artykuł')

@section('content')
    <form method="POST" action="{{ $article->exists ? route('admin.wiem-feer.update', $article) : route('admin.wiem-feer.store') }}"
        class="max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div>
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
            <input type="text" id="title" name="title" value="{{ old('title', $article->title) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="author_name" class="mb-1 block text-sm font-bold">Autor <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="author_name" name="author_name" value="{{ old('author_name', $article->author_name) }}"
                    placeholder="np. Zespół FEER"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('author_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">Adres URL (slug) <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $article->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="excerpt" class="mb-1 block text-sm font-bold">Zajawka <span class="font-normal text-muted">(opcjonalnie, na liście artykułów)</span></label>
            <textarea id="excerpt" name="excerpt" rows="2"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('excerpt', $article->excerpt) }}</textarea>
            @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="editor-body" class="mb-1 block text-sm font-bold">Treść</label>
            @include('admin.partials.editor', ['name' => 'body', 'value' => old('body', $article->body)])
            @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="published_at" class="mb-1 block text-sm font-bold">Data publikacji <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="datetime-local" id="published_at" name="published_at"
                value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}"
                class="w-full max-w-xs rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Pusto = data zapisania (przy publikacji). Data w przyszłości ukrywa artykuł do tego momentu.</p>
            @error('published_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $article->is_published ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Opublikowany</span>
        </label>

        {{-- ==================== DOSTĘPNOŚĆ ARTYKUŁU ==================== --}}
        @php $currentWip = old('wip_mode', $article->wip_mode); @endphp
        <div class="space-y-5 border-t border-gray-100 pt-5">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-muted">Dostępność artykułu</p>
                <p class="mt-1 text-xs text-muted">Tymczasowo wyłącz artykuł lub oznacz, że jest w przygotowaniu. Działa niezależnie od statusu publikacji.</p>
            </div>

            {{-- Wyłącz artykuł --}}
            <div>
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="is_disabled" value="1" {{ old('is_disabled', $article->is_disabled ?? false) ? 'checked' : '' }}
                        class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand" data-disable-toggle>
                    <span>
                        <span class="block text-sm font-bold">Wyłącz artykuł</span>
                        <span class="block text-xs text-muted">Odwiedzający zamiast treści zobaczą pełnoekranowy komunikat, że artykuł jest tymczasowo niedostępny.</span>
                    </span>
                </label>
                <div class="mt-3 sm:pl-6" data-disable-message>
                    <label for="disabled_message" class="mb-1 block text-sm font-bold">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="disabled_message" name="disabled_message" rows="2" placeholder="{{ \Modules\Blog\Models\BlogArticle::DEFAULT_DISABLED_MESSAGE }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('disabled_message', $article->disabled_message) }}</textarea>
                    <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu.</p>
                    @error('disabled_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Artykuł w przygotowaniu --}}
            <div>
                <p class="text-sm font-bold">Artykuł w przygotowaniu</p>
                <p class="mb-3 text-xs text-muted">Oznacz, że trwają prace nad artykułem — wybierz, jak poinformować odwiedzających.</p>

                <div class="space-y-2" data-wip-modes>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="wip_mode" value="" {{ ! $currentWip ? 'checked' : '' }}
                            class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm">Wyłączone <span class="text-muted">— artykuł działa normalnie</span></span>
                    </label>
                    @foreach (\Modules\Blog\Models\BlogArticle::WIP_MODES as $value => $label)
                        <label class="flex items-start gap-2">
                            <input type="radio" name="wip_mode" value="{{ $value }}" {{ $currentWip === $value ? 'checked' : '' }}
                                class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('wip_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="mt-3 sm:pl-6 {{ $currentWip ? '' : 'hidden' }}" data-wip-message>
                    <label for="wip_message" class="mb-1 block text-sm font-bold">Treść komunikatu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="wip_message" name="wip_message" rows="2" placeholder="{{ \Modules\Blog\Models\BlogArticle::DEFAULT_WIP_NOTICE_MESSAGE }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('wip_message', $article->wip_message) }}</textarea>
                    <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu dla wybranego trybu.</p>
                    @error('wip_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.wiem-feer.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>

    <script>
        (function () {
            // --- Availability: reveal each message box only when active ----
            const disableToggle = document.querySelector('[data-disable-toggle]');
            const disableMessage = document.querySelector('[data-disable-message]');
            if (disableToggle && disableMessage) {
                const syncDisable = function () { disableMessage.classList.toggle('hidden', !disableToggle.checked); };
                disableToggle.addEventListener('change', syncDisable);
                syncDisable();
            }

            const wipMessage = document.querySelector('[data-wip-message]');
            const wipRadios = document.querySelectorAll('[data-wip-modes] input[name="wip_mode"]');
            if (wipMessage && wipRadios.length) {
                const syncWip = function () {
                    const selected = document.querySelector('[data-wip-modes] input[name="wip_mode"]:checked');
                    wipMessage.classList.toggle('hidden', !selected || selected.value === '');
                };
                wipRadios.forEach(function (r) { r.addEventListener('change', syncWip); });
                syncWip();
            }
        })();
    </script>
@endsection
