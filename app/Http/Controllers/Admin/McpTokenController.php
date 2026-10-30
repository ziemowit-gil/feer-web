<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: konfiguracja serwera MCP — adres, tokeny dostępu (tworzenie i odwoływanie)
 * i gotowe polecenie dla Claude Code. Token widać tylko raz, tuż po utworzeniu.
 */
class McpTokenController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.mcp.index', [
            'tokens' => $request->user()->tokens()->orderByDesc('created_at')->get(),
            'endpoint' => url('/mcp/feer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'write' => ['nullable', 'boolean'],
        ]);

        $abilities = $request->boolean('write') ? ['mcp:read', 'mcp:write'] : ['mcp:read'];
        $token = $request->user()->createToken($data['name'], $abilities);

        return redirect()->route('admin.mcp.index')->with('new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return redirect()->route('admin.mcp.index')->with('status', 'Token został odwołany.');
    }
}
