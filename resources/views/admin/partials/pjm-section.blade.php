{{--
    Zwijana sekcja „Tłumaczenie na PJM” pod treścią (osobny formularz, jak ETR).
    Zmienne: $model (Page|News z pjm_video_url), $type ('podstrona'|'news'), $hidden (bool, opcjonalnie), $attrs (string, opcjonalnie).
--}}
<details class="pe-section rounded-md bg-white" data-pe-section="pjm" {!! $attrs ?? '' !!} @class(['hidden' => $hidden ?? false])>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-bold text-ink [&::-webkit-details-marker]:hidden">
        <span>Tłumaczenie na Polski Język Migowy (PJM) @if ($model->pjm_video_url)<span class="font-normal text-muted">— dodane</span>@endif</span>
        <i class="fa-solid fa-chevron-down text-sm text-muted transition-transform" aria-hidden="true"></i>
    </summary>
    <div class="border-t border-gray-100 p-4">
        <p class="mb-4 rounded-md bg-brand-light p-4 text-sm text-ink">
            Wideo z tłumaczem PJM pokaże się nad treścią po kliknięciu przycisku „Tłumaczenie na PJM”.
            Wklej link do filmu na YouTube (może być niepubliczny) lub Vimeo albo adres pliku MP4/WebM z biblioteki mediów.
            YouTube i Vimeo ładują się w trybie bez ciasteczek śledzących.
        </p>
        <form method="POST" action="{{ route('admin.pjm.update', ['type' => $type, 'id' => $model->id]) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="pjm_video_url" class="mb-1 block text-sm font-bold">Link do wideo PJM</label>
                <input type="url" id="pjm_video_url" name="pjm_video_url" inputmode="url" maxlength="500"
                    value="{{ old('pjm_video_url', $model->pjm_video_url) }}"
                    placeholder="https://www.youtube.com/watch?v=…" aria-describedby="pjm_video_url_help"
                    class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark">
                <p id="pjm_video_url_help" class="mt-1 text-xs text-muted">Zostaw puste i zapisz, aby usunąć tłumaczenie.</p>
                @error('pjm_video_url') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz PJM</button>
        </form>
    </div>
</details>
