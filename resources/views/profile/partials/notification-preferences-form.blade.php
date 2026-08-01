<div>
    <h2 class="text-lg font-semibold text-ink">Powiadomienia e-mail</h2>
    <p class="mt-1 text-sm text-muted">Wybierz, o czym chcesz być informowany(-a) na adres <strong>{{ auth()->user()->email }}</strong>.</p>

    <form method="POST" action="{{ route('profile.notifications') }}" class="mt-4 space-y-3">
        @csrf
        @method('PATCH')

        @php
            $prefs = auth()->user()->notification_preferences ?? [];
        @endphp

        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50">
            <input type="checkbox" name="task_assigned" value="1"
                @checked(($prefs['task_assigned'] ?? true) !== false)
                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
            <span>
                <span class="block text-sm font-semibold text-ink">Przypisanie zadania</span>
                <span class="block text-xs text-muted">Gdy ktoś przypisze mi nowe zadanie w panelu.</span>
            </span>
        </label>

        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50">
            <input type="checkbox" name="task_due_soon" value="1"
                @checked(($prefs['task_due_soon'] ?? true) !== false)
                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
            <span>
                <span class="block text-sm font-semibold text-ink">Przypomnienie o terminie</span>
                <span class="block text-xs text-muted">Gdy termin mojego zadania mija następnego dnia.</span>
            </span>
        </label>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit"
                class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                Zapisz ustawienia
            </button>

            @if (session('status') === 'notifications-updated')
                <p class="text-sm text-green-700" role="status">Zapisano.</p>
            @endif
        </div>
    </form>
</div>
