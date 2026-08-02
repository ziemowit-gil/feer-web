<p class="text-sm text-muted">
    Po usunięciu konta wszystkie jego dane zostaną trwale usunięte. Przed usunięciem pobierz wszelkie dane, które chcesz zachować.
</p>

<button type="button"
    x-data=""
    x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    class="mt-4 rounded border border-red-300 px-5 py-2 text-sm font-bold text-red-600 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
    Usuń konto
</button>

<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-bold text-ink">Na pewno chcesz usunąć konto?</h2>
        <p class="mt-2 text-sm text-muted">
            Operacja jest nieodwracalna. Wszystkie dane zostaną trwale usunięte. Potwierdź hasłem.
        </p>

        <div class="mt-5">
            <label for="del-password" class="mb-1 block text-sm font-bold">Hasło</label>
            <x-text-input id="del-password" name="password" type="password" class="block w-3/4" placeholder="Twoje hasło" />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="button" x-on:click="$dispatch('close')"
                class="rounded border border-gray-300 px-5 py-2 text-sm font-bold text-ink hover:bg-gray-50">
                Anuluj
            </button>
            <button type="submit"
                class="rounded bg-red-600 px-5 py-2 text-sm font-bold text-white hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                Tak, usuń konto
            </button>
        </div>
    </form>
</x-modal>
