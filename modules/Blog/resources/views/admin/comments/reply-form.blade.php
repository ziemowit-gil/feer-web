{{-- Odpowiedź redakcji pod komentarzem — publikowana od razu jako „Odpowiedź redakcji". Zmienne: $comment. --}}
<details class="mt-3" @if ($errors->has('reply_body') && (int) old('reply_to') === $comment->id) open @endif>
    <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-bold text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
        <i class="fa-solid fa-reply" aria-hidden="true"></i> Odpowiedz jako redakcja
    </summary>
    <form method="POST" action="{{ route('admin.komentarze-bloga.reply', $comment) }}" class="mt-2 space-y-2">
        @csrf
        <input type="hidden" name="reply_to" value="{{ $comment->id }}">
        <label for="reply-{{ $comment->id }}" class="block text-xs font-bold">Treść odpowiedzi{{ $comment->is_approved ? '' : ' (zatwierdzi też komentarz)' }}</label>
        <textarea id="reply-{{ $comment->id }}" name="reply_body" rows="3" maxlength="3000" required
            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ (int) old('reply_to') === $comment->id ? old('reply_body') : '' }}</textarea>
        @if ($errors->has('reply_body') && (int) old('reply_to') === $comment->id)
            <p class="text-sm text-red-700">{{ $errors->first('reply_body') }}</p>
        @endif
        <button type="submit" class="rounded bg-brand-dark px-3 py-1.5 text-xs font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Opublikuj odpowiedź</button>
    </form>
</details>
