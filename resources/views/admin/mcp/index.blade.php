@extends('admin.layout')

@section('title', 'Serwer MCP')

@section('content')
    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Serwer MCP</h1>
        <p class="text-sm text-muted">Dostęp asystentów AI (np. Claude) do treści serwisu: strony, działania i aktualności. Zapis ogranicza się do szkiców — publikacja zawsze w panelu.</p>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 rounded-md bg-brand-light p-3 text-sm text-ink">{{ session('status') }}</div>
    @endif

    @if (session('new_token'))
        <div role="alert" class="mb-6 rounded-md bg-brand-light p-4 text-sm text-ink">
            <p class="mb-2 font-bold">Skopiuj token teraz — nie zobaczysz go ponownie.</p>
            <label for="new-token" class="sr-only">Nowy token</label>
            <input id="new-token" type="text" readonly value="{{ session('new_token') }}" onfocus="this.select()"
                   class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 font-mono text-xs text-ink">
            <p class="mb-1 mt-4 font-bold">Claude Code — gotowe polecenie:</p>
            <pre class="overflow-x-auto rounded-md bg-white p-3 text-xs text-ink"><code>claude mcp add --transport http feer {{ $endpoint }} --header "Authorization: Bearer {{ session('new_token') }}"</code></pre>
            <p class="mb-1 mt-4 font-bold">Claude Desktop — gotowa konfiguracja (<code class="font-mono">claude_desktop_config.json</code>):</p>
            <pre class="overflow-x-auto rounded-md bg-white p-3 text-xs text-ink"><code>"feer": {
  "command": "npx",
  "args": [
    "-y", "mcp-remote",
    "{{ $endpoint }}",
    "--transport", "http-only",
    "--header", "Authorization:${FEER_AUTH}"
  ],
  "env": { "FEER_AUTH": "Bearer {{ session('new_token') }}" }
}</code></pre>
        </div>
    @endif

    <section class="mb-8" aria-labelledby="mcp-connect">
        <h2 id="mcp-connect" class="mb-2 text-base font-bold text-ink">Podłączenie</h2>
        <p class="mb-2 text-sm text-ink">Adres serwera: <code class="font-mono">{{ $endpoint }}</code></p>
        <p class="mb-1 text-sm text-ink">Polecenie dla Claude Code (wstaw swój token):</p>
        <pre class="overflow-x-auto rounded-md bg-gray-100 p-3 text-xs text-ink"><code>claude mcp add --transport http feer {{ $endpoint }} --header "Authorization: Bearer TOKEN"</code></pre>
        <p class="mb-1 mt-4 text-sm text-ink">Konfiguracja dla Claude Desktop (<code class="font-mono">claude_desktop_config.json</code>, wstaw swój token):</p>
        <pre class="overflow-x-auto rounded-md bg-gray-100 p-3 text-xs text-ink"><code>"feer": {
  "command": "npx",
  "args": [
    "-y", "mcp-remote",
    "{{ $endpoint }}",
    "--transport", "http-only",
    "--header", "Authorization:${FEER_AUTH}"
  ],
  "env": { "FEER_AUTH": "Bearer TOKEN" }
}</code></pre>
        <p class="mt-1 text-sm text-muted">Nagłówek zapisz bez spacji po dwukropku, a „Bearer …” podaj w zmiennej środowiskowej. Przełącznik <code class="font-mono">http-only</code> zapobiega mylącemu błędowi 405 z transportu SSE, którego serwer nie obsługuje.</p>

    <section class="mb-8" aria-labelledby="mcp-new">
        <h2 id="mcp-new" class="mb-2 text-base font-bold text-ink">Nowy token</h2>
        <form method="POST" action="{{ route('admin.mcp.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="token-name" class="mb-1 block text-sm font-bold text-ink">Nazwa (np. laptop)</label>
                <input id="token-name" name="name" type="text" required maxlength="60" value="{{ old('name') }}"
                       class="rounded-md border border-gray-300 px-3 py-2 text-sm text-ink">
                @error('name')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm text-ink">
                <input type="checkbox" name="write" value="1" class="rounded border-gray-400">
                Zezwól na tworzenie szkiców
            </label>
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1">Utwórz token</button>
        </form>
    </section>

    <section aria-labelledby="mcp-tokens">
        <h2 id="mcp-tokens" class="mb-2 text-base font-bold text-ink">Twoje tokeny</h2>
        @if ($tokens->isEmpty())
            <p class="text-sm text-muted">Nie utworzono jeszcze żadnego tokenu.</p>
        @else
            <table class="w-full text-left text-sm text-ink">
                <caption class="sr-only">Tokeny dostępu do serwera MCP</caption>
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pr-4">Nazwa</th>
                        <th scope="col" class="py-2 pr-4">Uprawnienia</th>
                        <th scope="col" class="py-2 pr-4">Ostatnie użycie</th>
                        <th scope="col" class="py-2"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($tokens as $t)
                    <tr class="border-t border-gray-200">
                        <th scope="row" class="py-2 pr-4 font-bold">{{ $t->name }}</th>
                        <td class="py-2 pr-4">{{ in_array('mcp:write', $t->abilities ?? [], true) ? 'odczyt i szkice' : 'odczyt' }}</td>
                        <td class="py-2 pr-4">{{ $t->last_used_at?->format('d.m.Y H:i') ?? 'nie używany' }}</td>
                        <td class="py-2">
                            <form method="POST" action="{{ route('admin.mcp.destroy', $t->id) }}" onsubmit="return confirm('Odwołać token „{{ e($t->name) }}”?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-bold text-red-800 underline focus:outline-none focus:ring-2 focus:ring-red-400">Odwołaj<span class="sr-only"> token {{ $t->name }}</span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
